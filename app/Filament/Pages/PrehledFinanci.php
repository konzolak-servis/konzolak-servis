<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Fakturas\FakturaResource;
use App\Filament\Resources\Nakups\NakupResource;
use App\Filament\Resources\SkladPolozkas\SkladPolozkaResource;
use App\Filament\Resources\Zakazkas\ZakazkaResource;
use App\Models\Faktura;
use App\Models\Nakup;
use App\Models\PenezniDenik;
use App\Models\SkladPolozka;
use App\Models\Zakazka;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

class PrehledFinanci extends Page
{
    protected string $view = 'filament.pages.prehled-financi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Přehled financí';

    protected static ?string $title = 'Přehled financí';

    public string $obdobi = 'mesic';   // den | mesic | rok

    public string $den = '';

    public string $mesic = '';

    public int $rok = 0;

    public function mount(): void
    {
        $this->den = now()->toDateString();
        $this->mesic = now()->format('Y-m');
        $this->rok = (int) now()->year;
    }

    /** Klik na měsíc v rozpadu → přepne období na daný měsíc. */
    public function otevriMesic(int $m): void
    {
        $rok = $this->obdobi === 'rok' ? $this->rok : (int) substr($this->mesic, 0, 4);
        $this->obdobi = 'mesic';
        $this->mesic = sprintf('%04d-%02d', $rok, $m);
    }

    protected function rozsah(): array
    {
        return match ($this->obdobi) {
            'den' => [Carbon::parse($this->den)->startOfDay(), Carbon::parse($this->den)->endOfDay()],
            'rok' => [Carbon::create($this->rok, 1, 1)->startOfDay(), Carbon::create($this->rok, 12, 31)->endOfDay()],
            default => [
                Carbon::parse($this->mesic . '-01')->startOfMonth(),
                Carbon::parse($this->mesic . '-01')->endOfMonth(),
            ],
        };
    }

    public function getObdobiLabelProperty(): string
    {
        Carbon::setLocale('cs');
        [$od] = $this->rozsah();

        return match ($this->obdobi) {
            'den' => $od->translatedFormat('j. F Y'),
            'rok' => (string) $this->rok,
            default => ucfirst($od->translatedFormat('F Y')),
        };
    }

    public function getStatyProperty(): array
    {
        [$od, $do] = $this->rozsah();

        $prijmy = (float) PenezniDenik::whereBetween('datum', [$od, $do])->where('typ', 'prijem')->sum('castka');
        $vydaje = (float) PenezniDenik::whereBetween('datum', [$od, $do])->where('typ', 'vydej')->sum('castka');

        $hodnotaSkladu = (float) SkladPolozka::query()
            ->selectRaw('COALESCE(SUM(mnozstvi_skladem * cena_ks_prumer), 0) AS h')
            ->value('h');

        return [
            'prijmy' => $prijmy,
            'vydaje' => $vydaje,
            'cisty' => $prijmy - $vydaje,
            'hodnota_skladu' => $hodnotaSkladu,
            'pocet_oprav' => Zakazka::where('stav', 'vydano')->whereBetween('datum_vyrizeni', [$od, $do])->count(),
        ];
    }

    public function getMesiceProperty(): array
    {
        $rok = $this->obdobi === 'rok' ? $this->rok : (int) substr($this->mesic, 0, 4);
        $aktivniMesic = $this->obdobi === 'mesic' ? (int) substr($this->mesic, 5, 2) : null;
        Carbon::setLocale('cs');

        $zaznamy = PenezniDenik::whereYear('datum', $rok)->get();

        $radky = [];
        foreach (range(1, 12) as $m) {
            $v = $zaznamy->filter(fn ($z) => (int) $z->datum->month === $m);
            $p = (float) $v->where('typ', 'prijem')->sum('castka');
            $x = (float) $v->where('typ', 'vydej')->sum('castka');
            $radky[] = [
                'cislo' => $m,
                'nazev' => ucfirst(Carbon::create($rok, $m, 1)->translatedFormat('F')),
                'prijmy' => $p,
                'vydaje' => $x,
                'cisty' => $p - $x,
                'pocet' => $v->count(),
                'vybrany' => $m === $aktivniMesic,
            ];
        }

        return ['rok' => $rok, 'radky' => $radky];
    }

    /** Všechny pohyby ve zvoleném období + proklik na doklad. */
    public function getPohybyProperty(): array
    {
        [$od, $do] = $this->rozsah();

        return PenezniDenik::whereBetween('datum', [$od, $do])
            ->orderBy('datum')->orderBy('id')
            ->get()
            ->map(fn (PenezniDenik $z) => [
                'datum' => $z->datum->format('d.m.Y'),
                'typ' => $z->typ,
                'popis' => $z->popis,
                'kategorie' => $z->kategorie,
                'kde' => $z->kde,
                'castka' => (float) $z->castka,
                'doklad' => $this->doklad($z),
            ])
            ->all();
    }

    /** [label, url] dokladu, ke kterému pohyb patří. */
    protected function doklad(PenezniDenik $z): array
    {
        return match ($z->zdroj) {
            'zakazka' => (function () use ($z) {
                $zk = Zakazka::find($z->zdroj_id);

                return $zk ? [$zk->cislo, ZakazkaResource::getUrl('edit', ['record' => $zk])] : [null, null];
            })(),
            'faktura' => (function () use ($z) {
                $f = Faktura::find($z->zdroj_id);

                return $f ? [$f->cislo, FakturaResource::getUrl('edit', ['record' => $f])] : [null, null];
            })(),
            'nakup' => (function () use ($z) {
                $n = Nakup::find($z->zdroj_id);

                return $n ? [$n->cislo, NakupResource::getUrl('edit', ['record' => $n])] : [null, null];
            })(),
            'obchod' => (function () use ($z) {
                $o = \App\Models\Obchod::find($z->zdroj_id);

                return $o ? [$o->cislo, \App\Filament\Resources\Obchods\ObchodResource::getUrl('edit', ['record' => $o])] : [null, null];
            })(),
            default => [$z->kde ?: '—', null],
        };
    }

    public function getSkladUrlProperty(): string
    {
        return SkladPolozkaResource::getUrl('index');
    }
}
