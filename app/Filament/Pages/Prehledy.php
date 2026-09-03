<?php

namespace App\Filament\Pages;

use App\Models\Obchod;
use App\Models\PenezniDenik;
use App\Models\Zakazka;
use App\Models\ZakazkaPolozka;
use App\Models\Zarizeni;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class Prehledy extends Page
{
    protected string $view = 'filament.pages.prehledy';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Přehledy a statistiky';

    protected static ?string $title = 'Přehledy a statistiky';

    public int $rok = 0;

    public function mount(): void
    {
        $this->rok = (int) now()->year;
    }

    public function getRokyProperty(): array
    {
        $min = (int) (PenezniDenik::min(DB::raw('YEAR(datum)')) ?: now()->year);

        return range((int) now()->year, min($min, (int) now()->year - 5));
    }

    /** Obrat podle druhu činnosti / kategorie zařízení. */
    public function getObratProperty(): array
    {
        $rok = $this->rok;
        $radky = [];

        $zk = Zakazka::with('zarizeni')
            ->whereIn('stav', ['vydano', 'nerentabilni'])
            ->whereYear('datum_vyrizeni', $rok)
            ->get()
            ->groupBy(fn ($z) => $z->zarizeni?->kategorie ?: 'jine');

        foreach ($zk as $kat => $skupina) {
            $radky[] = [
                'nazev' => 'Servis – ' . (Zarizeni::KATEGORIE[$kat] ?? 'ostatní'),
                'castka' => (float) $skupina->sum('cena_celkem'),
                'pocet' => $skupina->count(),
            ];
        }

        $fakt = PenezniDenik::whereYear('datum', $rok)->where('zdroj', 'faktura')->sum('castka');
        if ($fakt > 0) {
            $radky[] = ['nazev' => 'Faktury', 'castka' => (float) $fakt, 'pocet' => PenezniDenik::whereYear('datum', $rok)->where('zdroj', 'faktura')->count()];
        }

        $bazarProdej = Obchod::where('typ', 'prodej')->where('vyrizeno', true)->whereYear('datum', $rok)->sum('cena');
        if ($bazarProdej > 0) {
            $radky[] = ['nazev' => 'Bazar – prodej', 'castka' => (float) $bazarProdej, 'pocet' => Obchod::where('typ', 'prodej')->where('vyrizeno', true)->whereYear('datum', $rok)->count()];
        }

        usort($radky, fn ($a, $b) => $b['castka'] <=> $a['castka']);
        $max = collect($radky)->max('castka') ?: 1;

        return ['radky' => $radky, 'max' => $max, 'celkem' => collect($radky)->sum('castka')];
    }

    /** Bazar – výkup vs. prodej. */
    public function getBazarProperty(): array
    {
        $rok = $this->rok;
        $vykup = (float) Obchod::where('typ', 'vykup')->where('vyrizeno', true)->whereYear('datum', $rok)->sum('cena');
        $prodej = (float) Obchod::where('typ', 'prodej')->where('vyrizeno', true)->whereYear('datum', $rok)->sum('cena');

        return [
            'vykup' => $vykup,
            'prodej' => $prodej,
            'zisk' => $prodej - $vykup,
            'pocet_vykup' => Obchod::where('typ', 'vykup')->where('vyrizeno', true)->whereYear('datum', $rok)->count(),
            'pocet_prodej' => Obchod::where('typ', 'prodej')->where('vyrizeno', true)->whereYear('datum', $rok)->count(),
        ];
    }

    /** Nejčastější prováděné práce. */
    public function getNejcastejsiProperty(): array
    {
        $rok = $this->rok;

        $rows = ZakazkaPolozka::query()
            ->join('zakazky', 'zakazky.id', '=', 'zakazka_polozky.zakazka_id')
            ->where('zakazka_polozky.typ', 'prace')
            ->whereIn('zakazky.stav', ['vydano', 'nerentabilni'])
            ->whereYear('zakazky.datum_vyrizeni', $rok)
            ->groupBy('zakazka_polozky.nazev')
            ->selectRaw('zakazka_polozky.nazev, COUNT(*) as pocet, SUM(zakazka_polozky.cena_celkem) as castka')
            ->orderByDesc('pocet')
            ->limit(12)
            ->get()
            ->toArray();

        $max = collect($rows)->max('pocet') ?: 1;

        return ['radky' => $rows, 'max' => $max];
    }

    /** Obrat po měsících (servis + faktury + bazar prodej). */
    public function getMesiceProperty(): array
    {
        $rok = $this->rok;
        Carbon::setLocale('cs');
        $out = [];

        for ($m = 1; $m <= 12; $m++) {
            $servis = (float) Zakazka::whereIn('stav', ['vydano', 'nerentabilni'])
                ->whereYear('datum_vyrizeni', $rok)->whereMonth('datum_vyrizeni', $m)->sum('cena_celkem');
            $fakt = (float) PenezniDenik::where('zdroj', 'faktura')->whereYear('datum', $rok)->whereMonth('datum', $m)->sum('castka');
            $bazar = (float) Obchod::where('typ', 'prodej')->where('vyrizeno', true)
                ->whereYear('datum', $rok)->whereMonth('datum', $m)->sum('cena');

            $out[] = [
                'nazev' => ucfirst(Carbon::create($rok, $m, 1)->translatedFormat('M')),
                'castka' => $servis + $fakt + $bazar,
            ];
        }

        $max = collect($out)->max('castka') ?: 1;

        return ['radky' => $out, 'max' => $max];
    }
}
