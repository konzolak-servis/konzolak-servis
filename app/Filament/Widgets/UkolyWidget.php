<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Fakturas\FakturaResource;
use App\Filament\Resources\ObjednavkaDilus\ObjednavkaDiluResource;
use App\Filament\Resources\Pristups\PristupResource;
use App\Filament\Resources\Zakazkas\ZakazkaResource;
use App\Models\Faktura;
use App\Models\ObjednavkaDilu;
use App\Models\Pristup;
use App\Models\Zakazka;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UkolyWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.ukoly-widget';

    // hned pod widgetem Pošta (-2), nad číselnými dlaždicemi (0)
    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    /** Max. řádků v jedné sekci; zbytek se schová za odkaz „další". */
    private const LIMIT = 8;

    protected function getViewData(): array
    {
        $dnes = Carbon::today();
        $skupiny = [];
        $hotovoIds = [];

        // 1) Faktury po splatnosti ------------------------------------------------
        $poSplatnosti = Faktura::with('zakaznik')
            ->where('uhrazeno', false)
            ->whereNotNull('datum_splatnosti')
            ->whereDate('datum_splatnosti', '<', $dnes)
            ->orderBy('datum_splatnosti')
            ->get()
            ->map(fn (Faktura $f) => [
                'cislo' => $f->cislo,
                'popis' => $f->zakaznik?->nazev ?? '—',
                'meta' => 'po splatnosti ' . (int) $dnes->diffInDays($f->datum_splatnosti) . ' d',
                'castka' => $f->celkem,
                'tag' => null,
                'urgent' => true,
                'url' => FakturaResource::getUrl('edit', ['record' => $f->id]),
            ]);
        $this->pridej($skupiny, 'faktury_po', 'Faktury po splatnosti', 'danger', $poSplatnosti,
            FakturaResource::getUrl('index'));

        // 2) Díl dorazil – vrátit do práce -------------------------------------
        $dilDorazil = ObjednavkaDilu::with(['zakazka.zakaznik', 'zakazka.zarizeni'])
            ->where('stav', 'dorazilo')
            ->whereHas('zakazka', fn ($q) => $q->whereNotIn('stav', ['hotovo', 'vydano', 'storno', 'nerentabilni']))
            ->get()
            ->groupBy('zakazka_id')
            ->map(function ($objednavky) {
                /** @var \App\Models\ObjednavkaDilu $o */
                $o = $objednavky->first();
                $z = $o->zakazka;

                return [
                    'cislo' => $z->cislo,
                    'popis' => $this->popisZakazky($z),
                    'meta' => $o->doruceno_datum ? 'díl doručen ' . $o->doruceno_datum->format('d.m.') : 'díl doručen',
                    'castka' => null,
                    'tag' => $objednavky->count() > 1 ? $objednavky->count() . '× díl' : Str::limit($o->nazev_dilu, 28),
                    'urgent' => true,
                    'url' => ZakazkaResource::getUrl('edit', ['record' => $z->id]),
                    '_id' => $z->id,
                ];
            })
            ->values();
        $hotovoIds = array_merge($hotovoIds, $dilDorazil->pluck('_id')->all());
        $this->pridej($skupiny, 'dil_dorazil', 'Díl dorazil – pokračovat v opravě', 'success', $dilDorazil);

        // 3) Hotové k vydání ----------------------------------------------------
        $kVydani = Zakazka::with(['zakaznik', 'zarizeni', 'dalsiZarizeni'])
            ->where('stav', 'hotovo')
            ->orderBy('datum_vyrizeni')
            ->get()
            ->map(function (Zakazka $z) use ($dnes, &$hotovoIds) {
                $hotovoIds[] = $z->id;
                $od = $z->datum_vyrizeni ?? $z->updated_at;
                $dni = $od ? (int) $dnes->diffInDays(Carbon::parse($od)->startOfDay()) : 0;

                return [
                    'cislo' => $z->cislo,
                    'popis' => $this->popisZakazky($z),
                    'meta' => $dni <= 0 ? 'hotovo dnes' : 'čeká ' . $dni . ' d',
                    'castka' => $z->cena_celkem > 0 ? $z->cena_celkem : null,
                    'tag' => null,
                    'urgent' => $dni >= 14,
                    'url' => ZakazkaResource::getUrl('edit', ['record' => $z->id]),
                ];
            });
        $this->pridej($skupiny, 'k_vydani', 'Hotové k vydání', 'success', $kVydani,
            ZakazkaResource::getUrl('index', ['tableFilters' => ['stav' => ['value' => 'hotovo']]]));

        // 4) Čeká na díl ------------------------------------------------------
        $cekaNaDil = Zakazka::with(['zakaznik', 'zarizeni', 'dalsiZarizeni'])
            ->where('stav', 'ceka_na_dil')
            ->orderBy('datum_prijeti')
            ->get()
            ->map(function (Zakazka $z) use (&$hotovoIds) {
                $hotovoIds[] = $z->id;

                // Objednávky dílů k zakázce – krátký štítek s dílem a datum místo celé poznámky.
                $objednane = ObjednavkaDilu::where('zakazka_id', $z->id)->where('stav', 'objednano')
                    ->orderBy('datum_objednavky')->get();
                $o = $objednane->first();

                $meta = match (true) {
                    (bool) $o?->ocekavane_doruceni => 'doručení ' . $o->ocekavane_doruceni->format('d.m.'),
                    (bool) $o?->datum_objednavky => 'objednáno ' . $o->datum_objednavky->format('d.m.'),
                    filled($z->dil_info) => Str::limit(trim(preg_replace('/\s+/', ' ', $z->dil_info)), 28),
                    default => null,
                };

                return [
                    'cislo' => $z->cislo,
                    'popis' => $this->popisZakazky($z),
                    'meta' => $meta,
                    'castka' => null,
                    'tag' => match (true) {
                        $objednane->count() > 1 => $objednane->count() . '× díl',
                        (bool) $o => Str::limit($o->nazev_dilu, 28),
                        default => $z->dil_objednany ? 'díl objednán' : 'díl NEobjednán',
                    },
                    'urgent' => ! $z->dil_objednany,
                    'url' => ZakazkaResource::getUrl('edit', ['record' => $z->id]),
                ];
            });
        $this->pridej($skupiny, 'ceka_na_dil', 'Čeká na díl', 'warning', $cekaNaDil,
            ZakazkaResource::getUrl('index', ['tableFilters' => ['stav' => ['value' => 'ceka_na_dil']]]));

        // 4b) Zápůjčky – po termínu, vrácení dnes/zítra, oprava hotová a zápůjčka pořád venku ----
        $zapujcky = \App\Models\Zapujcka::with(['zakaznik', 'zakazka', 'polozky'])
            ->where('stav', 'zapujceno')->get()
            ->map(function (\App\Models\Zapujcka $z) use ($dnes) {
                $opravaHotova = $z->zakazka && in_array($z->zakazka->stav, ['hotovo', 'vydano', 'nerentabilni', 'storno'], true);
                $dni = $z->vratit_do ? (int) $dnes->diffInDays($z->vratit_do, false) : null;

                $meta = match (true) {
                    $opravaHotova => 'oprava ' . ($z->zakazka->stav === 'hotovo' ? 'hotová' : 'vydaná') . ' – vrátit',
                    $dni !== null && $dni < 0 => 'po termínu ' . abs($dni) . ' d',
                    $dni === 0 => 'vrátit dnes',
                    $dni === 1 => 'vrátit zítra',
                    default => null,
                };
                if ($meta === null) {
                    return null; // běží v pořádku
                }

                return [
                    'cislo' => $z->cislo,
                    'popis' => trim(($z->zakaznik?->nazev ?? '—') . ' · ' . $z->polozky->pluck('nazev')->implode(', '), ' ·'),
                    'meta' => $meta,
                    'castka' => null,
                    'tag' => $z->zakazka?->cislo,
                    'urgent' => $opravaHotova || ($dni !== null && $dni < 0),
                    'url' => \App\Filament\Resources\Zapujckas\ZapujckaResource::getUrl('edit', ['record' => $z->id]),
                    '_sort' => $opravaHotova ? -999 : ($dni ?? 99),
                ];
            })
            ->filter()->sortBy('_sort')->values();
        $this->pridej($skupiny, 'zapujcky', 'Zápůjčky k vrácení', 'danger', $zapujcky,
            \App\Filament\Resources\Zapujckas\ZapujckaResource::getUrl('index'));

        // 4c0) Připomínka nastavení EET 2.0 (povinné od 1. 1. 2027) -------------
        $firma = \App\Models\Firma::get();
        $eetNastaveno = filled($firma->eet_dic) && filled($firma->eet_id_jednotky) && filled($firma->eet_cert_soubor);
        $eetUkoly = collect();
        $nastaveniUrl = \App\Filament\Pages\NastaveniFirmy::getUrl();
        if (! $eetNastaveno && $dnes->gte('2026-12-01')) {
            $eetUkoly->push([
                'cislo' => 'EET 2.0',
                'popis' => 'MOJE daně → Evidence tržeb: založ provozovnu a pokladní certifikát, pak vyplň Nastavení firmy → EET a otestuj spojení',
                'meta' => 'do 31. 12.',
                'castka' => null, 'tag' => 'nastavit',
                'urgent' => $dnes->gte('2026-12-20'),
                'url' => $nastaveniUrl,
            ]);
        }
        if ($eetNastaveno && $dnes->gte('2027-01-01') && (! $firma->eet_aktivni || $firma->eet_overeni || $firma->eet_prostredi !== 'produkce')) {
            $eetUkoly->push([
                'cislo' => 'EET 2.0',
                'popis' => 'Zapni ostré odesílání: prostředí Ostré, „Odesílat tržby do EET" zapnout, ověřovací režim vypnout',
                'meta' => 'povinné od 1. 1.',
                'castka' => null, 'tag' => 'zapnout',
                'urgent' => true,
                'url' => $nastaveniUrl,
            ]);
        }
        $this->pridej($skupiny, 'eet_nastaveni', 'Připomínka – EET 2.0', 'danger', $eetUkoly);

        // 4c) EET – neodeslané tržby (zákonná lhůta 48 h) ---------------------
        $eet = \App\Models\EetTrzba::where('stav', '!=', 'odeslano')->orderBy('id')->get()
            ->map(fn (\App\Models\EetTrzba $t) => [
                'cislo' => 'EET ' . $t->porad_cis,
                'popis' => $t->popis,
                'meta' => $t->stav === 'chyba' ? 'chyba: ' . \Illuminate\Support\Str::limit((string) $t->chyba_text, 40) : 'čeká na odeslání',
                'castka' => $t->celk_trzba,
                'tag' => null,
                'urgent' => $t->stav === 'chyba' || $t->created_at->lt(now()->subHours(24)),
                'url' => \App\Filament\Resources\EetTrzbas\EetTrzbaResource::getUrl('index'),
            ]);
        $this->pridej($skupiny, 'eet', 'EET – neodeslané tržby', 'danger', $eet,
            \App\Filament\Resources\EetTrzbas\EetTrzbaResource::getUrl('index'));

        // 5) Dlouho bez pohybu ---------------------------------------------
        $stoji = Zakazka::with(['zakaznik', 'zarizeni', 'dalsiZarizeni'])
            ->whereIn('stav', ['prijato', 'diagnostika'])
            ->whereDate('updated_at', '<', $dnes->copy()->subDays(14))
            ->when($hotovoIds, fn ($q) => $q->whereNotIn('id', $hotovoIds))
            ->orderBy('updated_at')
            ->get()
            ->map(fn (Zakazka $z) => [
                'cislo' => $z->cislo,
                'popis' => $this->popisZakazky($z),
                'meta' => 'beze změny ' . (int) $dnes->diffInDays(Carbon::parse($z->updated_at)->startOfDay())
                    . ' d · ' . (Zakazka::STAVY[$z->stav] ?? $z->stav),
                'castka' => null,
                'tag' => null,
                'urgent' => false,
                'url' => ZakazkaResource::getUrl('edit', ['record' => $z->id]),
            ]);
        $this->pridej($skupiny, 'stoji', 'Dlouho bez pohybu', 'gray', $stoji,
            ZakazkaResource::getUrl('index'));

        // 6) Blíží se obnova / dodání ------------------------------------------
        $obnova = collect();
        foreach (Pristup::where('aktivni', true)->whereNotNull('platnost_do')->get() as $p) {
            if ($p->jeNaSpadnuti() && $p->dniDoKonce() !== null && $p->dniDoKonce() >= 0) {
                $obnova->push([
                    'cislo' => Pristup::KATEGORIE[$p->kategorie] ?? 'Přístup',
                    'popis' => $p->nazev,
                    'meta' => 'obnova za ' . $p->dniDoKonce() . ' d (' . $p->platnost_do->format('d.m.Y') . ')',
                    'castka' => $p->castka,
                    'tag' => null,
                    'urgent' => $p->dniDoKonce() <= 7,
                    'url' => PristupResource::getUrl('edit', ['record' => $p->id]),
                    '_sort' => $p->dniDoKonce(),
                ]);
            }
        }
        foreach (ObjednavkaDilu::with('zakazka')->where('stav', 'objednano')->whereNotNull('ocekavane_doruceni')->get() as $o) {
            $dni = (int) $dnes->diffInDays($o->ocekavane_doruceni, false);
            if ($dni <= 3) {
                $obnova->push([
                    'cislo' => $o->cislo,
                    'popis' => $o->nazev_dilu . ($o->zakazka ? ' · ' . $o->zakazka->cislo : ''),
                    'meta' => $dni < 0 ? 'termín před ' . abs($dni) . ' d' : ($dni === 0 ? 'termín dnes' : 'termín za ' . $dni . ' d'),
                    'castka' => $o->cena_odhad,
                    'tag' => null,
                    'urgent' => $dni < 0,
                    'url' => ObjednavkaDiluResource::getUrl('edit', ['record' => $o->id]),
                    '_sort' => $dni,
                ]);
            }
        }
        $this->pridej($skupiny, 'obnova', 'Blíží se obnova / dodání', 'info',
            $obnova->sortBy('_sort')->values());

        return [
            'skupiny' => $skupiny,
            'celkem' => collect($skupiny)->sum('pocet'),
        ];
    }

    private function popisZakazky(Zakazka $z): string
    {
        return trim(($z->zakaznik?->nazev ?? '—') . ' · ' . $z->zarizeniText(), " ·\t\n");
    }

    /** Přidá sekci do seznamu, pokud má aspoň jednu položku. */
    private function pridej(array &$skupiny, string $klic, string $nadpis, string $barva, $polozky, ?string $vseUrl = null): void
    {
        $polozky = collect($polozky)->values();
        if ($polozky->isEmpty()) {
            return;
        }

        $skupiny[] = [
            'klic' => $klic,
            'nadpis' => $nadpis,
            'barva' => $barva,
            'pocet' => $polozky->count(),
            'polozky' => $polozky->take(self::LIMIT)->all(),
            'skryto' => max(0, $polozky->count() - self::LIMIT),
            'vse_url' => $vseUrl,
        ];
    }
}
