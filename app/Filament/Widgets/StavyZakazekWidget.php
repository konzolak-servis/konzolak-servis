<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Kalendar;
use App\Filament\Resources\Nakups\NakupResource;
use App\Filament\Resources\ObjednavkaDilus\ObjednavkaDiluResource;
use App\Filament\Resources\PenezniDeniks\PenezniDenikResource;
use App\Filament\Resources\Zakazkas\ZakazkaResource;
use App\Models\Nakup;
use App\Models\ObjednavkaDilu;
use App\Models\PenezniDenik;
use App\Models\Zakazka;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StavyZakazekWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    // číselné dlaždice až pod panelem „Co řešit" (-1)
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $otevrene = Zakazka::whereNotIn('stav', ['vydano', 'storno'])->count();
        $cekaNaDil = Zakazka::where('stav', 'ceka_na_dil')->count();
        $kVydani = Zakazka::where('stav', 'hotovo')->count();
        $tentoMesic = Zakazka::whereYear('datum_prijeti', now()->year)
            ->whereMonth('datum_prijeti', now()->month)->count();
        $balikyDily = ObjednavkaDilu::where('stav', 'objednano')->count();
        $balikyNakupy = Nakup::where('naskladneno', false)->count();
        $ocekavaneBaliky = $balikyDily + $balikyNakupy;

        [$prijmy, $vydaje, $zisky] = $this->financeMesicne(6);
        $kc = fn (float $v) => number_format($v, 0, ',', ' ') . ' Kč';
        $mesic = now()->translatedFormat('F Y');

        $zakazkyUrl = fn (array $filters = []) => ZakazkaResource::getUrl('index', $filters);

        return [
            Stat::make('Otevřené zakázky', $otevrene)
                ->description('rozpracované, nevydané')
                ->color('warning')
                ->icon('heroicon-o-wrench-screwdriver')
                ->url($zakazkyUrl(['tableFilters' => ['otevrene' => ['isActive' => true]]])),

            Stat::make('Hotové k vydání', $kVydani)
                ->description('čekají na zákazníka')
                ->color($kVydani > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-check-badge')
                ->url($zakazkyUrl(['tableFilters' => ['stav' => ['value' => 'hotovo']]])),

            Stat::make('Čeká na díl', $cekaNaDil)
                ->description('blokované zakázky')
                ->color($cekaNaDil > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-truck')
                ->url($zakazkyUrl(['tableFilters' => ['stav' => ['value' => 'ceka_na_dil']]])),

            Stat::make('Přijato tento měsíc', $tentoMesic)
                ->description($mesic)
                ->color('info')
                ->icon('heroicon-o-calendar-days')
                ->url(Kalendar::getUrl()),

            Stat::make('Očekávané balíky', $ocekavaneBaliky)
                ->description(trim(
                    ($balikyNakupy ? $balikyNakupy . '× nákup' : '')
                    . ($balikyNakupy && $balikyDily ? ' · ' : '')
                    . ($balikyDily ? $balikyDily . '× díl' : '')
                ) ?: 'nic na cestě')
                ->color($ocekavaneBaliky > 0 ? 'info' : 'gray')
                ->icon('heroicon-o-inbox-arrow-down')
                ->url($balikyDily && ! $balikyNakupy
                    ? ObjednavkaDiluResource::getUrl('index', ['tableFilters' => ['stav' => ['value' => 'objednano']]])
                    : NakupResource::getUrl('index', ['tableFilters' => ['naskladneno' => ['value' => '0']]])),

            Stat::make('Čistý zisk – ' . $mesic, $kc(end($zisky)))
                ->description('Příjem ' . $kc(end($prijmy)) . '   ·   Výdej ' . $kc(end($vydaje)))
                ->color(end($zisky) >= 0 ? 'warning' : 'danger')
                ->icon('heroicon-o-banknotes')
                ->chart($zisky)
                ->url(PenezniDenikResource::getUrl('index')),
        ];
    }

    /**
     * Součty z peněžního deníku po měsících (nejstarší → aktuální).
     *
     * @return array{0: array<int>, 1: array<int>, 2: array<int>}  [příjmy, výdaje, zisky]
     */
    private function financeMesicne(int $mesicu): array
    {
        $prijmy = [];
        $vydaje = [];
        $zisky = [];

        for ($i = $mesicu - 1; $i >= 0; $i--) {
            $m = now()->copy()->subMonthsNoOverflow($i);
            $q = PenezniDenik::whereYear('datum', $m->year)->whereMonth('datum', $m->month);

            $p = (int) round((float) (clone $q)->where('typ', 'prijem')->sum('castka'));
            $v = (int) round((float) (clone $q)->where('typ', 'vydej')->sum('castka'));

            $prijmy[] = $p;
            $vydaje[] = $v;
            $zisky[] = $p - $v;
        }

        return [$prijmy, $vydaje, $zisky];
    }
}
