<?php

namespace App\Models;

use App\Filament\Resources\Fakturas\FakturaResource;
use App\Filament\Resources\Nakups\NakupResource;
use App\Filament\Resources\Obchods\ObchodResource;
use App\Filament\Resources\Zakazkas\ZakazkaResource;
use Illuminate\Database\Eloquent\Model;

class PenezniDenik extends Model
{
    protected $table = 'penezni_denik';

    protected $guarded = [];

    protected $casts = [
        'datum' => 'date',
        'castka' => 'decimal:2',
    ];

    /** Skupiny pro záložky / filtrovaný export. */
    public const SKUPINY = [
        'opravy' => 'Opravy',
        'faktury' => 'Faktury',
        'material' => 'Nákup dílů',
        'bazar' => 'Bazar',
        'ostatni' => 'Ostatní',
    ];

    public function scopeSkupina($query, ?string $skupina)
    {
        return match ($skupina) {
            'opravy' => $query->whereIn('zdroj', ['zakazka', 'zakazka_zaloha', 'zakazka_postovne']),
            'faktury' => $query->where('zdroj', 'faktura'),
            'bazar' => $query->where('zdroj', 'obchod'),
            'material' => $query->where('zdroj', 'nakup'),
            'ostatni' => $query->whereNull('zdroj')->orWhere('zdroj', 'rucne'),
            default => $query,
        };
    }

    /** [popisek, url] zdrojového dokladu. */
    public function doklad(): array
    {
        return match ($this->zdroj) {
            'zakazka', 'zakazka_zaloha', 'zakazka_postovne' => $this->odkaz(Zakazka::class,
                ZakazkaResource::class),
            'faktura' => $this->odkaz(Faktura::class,
                FakturaResource::class),
            'nakup' => $this->odkaz(Nakup::class,
                NakupResource::class),
            'obchod' => $this->odkaz(Obchod::class,
                ObchodResource::class),
            default => [$this->kde ?: null, null],
        };
    }

    private function odkaz(string $model, string $resource): array
    {
        $rec = $model::find($this->zdroj_id);

        return $rec ? [$rec->cislo, $resource::getUrl('edit', ['record' => $rec])] : [null, null];
    }

    /**
     * Rozpis a doklady zdrojového záznamu (nákup, zakázka, faktura, bazar) pro stránku úpravy záznamu:
     * co bylo koupeno / účtováno a odkazy na nahraný doklad, fakturu nebo protokol. Null, když záznam nemá zdroj.
     */
    public function zdrojHtml(): ?string
    {
        if (! $this->zdroj || ! $this->zdroj_id) {
            return null;
        }

        $e = fn ($t) => e((string) $t);
        $kc = fn ($c) => number_format((float) $c, 2, ',', ' ') . ' Kč';
        $odkaz = fn (string $url, string $text, bool $novy = false) => '<a href="' . e($url) . '"' . ($novy ? ' target="_blank" rel="noopener"' : '')
            . ' style="color:#d6a73e;text-decoration:underline">' . e($text) . '</a>';
        $radky = [];
        $doklady = [];

        switch ($this->zdroj) {
            case 'nakup':
                $n = Nakup::with(['polozky.zakazka'])->find($this->zdroj_id);
                if (! $n) {
                    return '<em>Zdrojový nákup už neexistuje.</em>';
                }
                $radky[] = '<strong>' . $e($n->cislo) . '</strong> · ' . $e($n->dodavatel ?: 'bez dodavatele')
                    . ' · nákup ' . $e($n->datum?->format('d.m.Y'))
                    . ($n->datum_naskladneni ? ' · naskladněno ' . $e($n->datum_naskladneni->format('d.m.Y')) : ' · zatím nenaskladněno');
                foreach ($n->polozky as $p) {
                    $radky[] = '• ' . $e($p->nazev) . ' – ' . rtrim(rtrim(number_format((float) $p->mnozstvi_ks, 3, ',', ' '), '0'), ',') . ' ks, ' . $kc($p->castka_celkem)
                        . ($p->neskladovat ? ' <span style="opacity:.7">(náklad firmy, neskladuje se)</span>' : '')
                        . ($p->zakazka ? ' · pro zakázku ' . $odkaz(\App\Filament\Resources\Zakazkas\ZakazkaResource::getUrl('edit', ['record' => $p->zakazka]), $p->zakazka->cislo) : '');
                }
                if ((float) $n->postovne > 0) {
                    $radky[] = '• poštovné ' . $kc($n->postovne);
                }
                if ($n->doklad_soubor) {
                    $doklady[] = $odkaz(\Illuminate\Support\Facades\Storage::disk('public')->url($n->doklad_soubor), '📎 Zobrazit nahraný doklad (' . basename($n->doklad_soubor) . ')', true);
                } else {
                    $doklady[] = '<span style="opacity:.7">Doklad k nákupu zatím není nahraný.</span>';
                }
                $doklady[] = $odkaz(\App\Filament\Resources\Nakups\NakupResource::getUrl('edit', ['record' => $n]), 'Otevřít nákup');
                break;

            case 'zakazka':
            case 'zakazka_zaloha':
            case 'zakazka_postovne':
                $z = Zakazka::with('polozky')->find($this->zdroj_id);
                if (! $z) {
                    return '<em>Zdrojová zakázka už neexistuje.</em>';
                }
                $radky[] = '<strong>' . $e($z->cislo) . '</strong> · ' . $e($z->zakaznik?->nazev) . ' · ' . $e($z->zarizeniText());
                foreach ($z->polozky->where('uctovat', true) as $p) {
                    $radky[] = '• ' . $e($p->nazev) . ' – ' . rtrim(rtrim(number_format((float) $p->mnozstvi, 3, ',', ' '), '0'), ',') . ' × ' . $kc($p->cena_ks) . ' = ' . $kc($p->cena_celkem);
                }
                if ($z->slevaCastka() > 0) {
                    $radky[] = '• ' . $e(mb_strtolower(mb_substr($z->slevaPopis(), 0, 1)) . mb_substr($z->slevaPopis(), 1)) . ': −' . $kc($z->slevaCastka());
                }
                if ((float) $z->cena_dopravy > 0) {
                    $radky[] = '• doprava ' . $kc($z->cena_dopravy);
                }
                if ((float) $z->zaloha > 0) {
                    $radky[] = '• přijatá záloha ' . $kc($z->zaloha);
                }
                if ($this->zdroj === 'zakazka_postovne') {
                    $radky[] = '• poštovné zaplacené servisem: ' . $kc($z->postovne_naklad);
                }
                $doklady[] = $odkaz($z->tiskUrl('protokol'), '📄 Zobrazit servisní protokol (PDF)', true);
                $doklady[] = $odkaz($z->tiskUrl('doklad'), 'Doklad o převzetí (PDF)', true);
                if ($z->faktura) {
                    $doklady[] = $odkaz($z->faktura->tiskUrl(), 'Faktura ' . $z->faktura->cislo . ' (PDF)', true);
                }
                $doklady[] = $odkaz(\App\Filament\Resources\Zakazkas\ZakazkaResource::getUrl('edit', ['record' => $z]), 'Otevřít zakázku');
                break;

            case 'faktura':
                $f = Faktura::with(['polozky', 'zakaznik'])->find($this->zdroj_id);
                if (! $f) {
                    return '<em>Zdrojová faktura už neexistuje.</em>';
                }
                $radky[] = '<strong>' . $e($f->cislo) . '</strong> · ' . $e($f->zakaznik?->nazev) . ' · VS ' . $e($f->variabilni_symbol);
                foreach ($f->polozky as $p) {
                    $radky[] = '• ' . $e($p->popis) . ' – ' . $kc($p->cena_celkem);
                }
                $doklady[] = $odkaz($f->tiskUrl(), '📄 Zobrazit fakturu (PDF)', true);
                $doklady[] = $odkaz(\App\Filament\Resources\Fakturas\FakturaResource::getUrl('edit', ['record' => $f]), 'Otevřít fakturu');
                break;

            case 'obchod':
                $o = Obchod::find($this->zdroj_id);
                if (! $o) {
                    return '<em>Zdrojový bazarový záznam už neexistuje.</em>';
                }
                $radky[] = '<strong>' . $e($o->cislo) . '</strong> · ' . $e($o->nazev) . ($o->seriove_cislo ? ' · SN ' . $e($o->seriove_cislo) : '');
                $radky[] = '• výkup ' . $kc($o->cena) . ' (' . $e($o->datum?->format('d.m.Y')) . ')' . ($o->protistrana_jmeno ? ', od ' . $e($o->protistrana_jmeno) : '');
                if ($o->prodano) {
                    $radky[] = '• prodej ' . $kc($o->prodejni_cena) . ' (' . $e($o->prodej_datum?->format('d.m.Y')) . ')' . ($o->prodej_komu ? ', ' . $e($o->prodej_komu) : '');
                }
                $doklady[] = $odkaz($o->tiskUrl(), '📄 Zobrazit doklad o výkupu (PDF)', true);
                if ($o->prodano) {
                    $doklady[] = $odkaz($o->tiskUrl(['typ' => 'prodej']), 'Doklad o prodeji (PDF)', true);
                }
                $doklady[] = $odkaz(\App\Filament\Resources\Obchods\ObchodResource::getUrl('edit', ['record' => $o]), 'Otevřít bazarový záznam');
                break;

            default:
                return null;
        }

        return '<div style="line-height:1.6">' . implode('<br>', $radky)
            . '<div style="margin-top:.6rem;display:flex;flex-wrap:wrap;gap:.4rem 1.2rem">' . implode('', array_map(fn ($d) => '<span>' . $d . '</span>', $doklady)) . '</div></div>';
    }
}
