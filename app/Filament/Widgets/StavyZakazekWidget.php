<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Kalendar;
use App\Filament\Resources\SkladPolozkas\SkladPolozkaResource;
use App\Filament\Resources\Zakazkas\ZakazkaResource;
use App\Models\SkladPolozka;
use App\Models\Zakazka;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StavyZakazekWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $otevrene = Zakazka::whereNotIn('stav', ['vydano', 'storno'])->count();
        $cekaNaDil = Zakazka::where('stav', 'ceka_na_dil')->count();
        $kVydani = Zakazka::where('stav', 'hotovo')->count();
        $tentoMesic = Zakazka::whereYear('datum_prijeti', now()->year)
            ->whereMonth('datum_prijeti', now()->month)->count();
        $podMinimem = SkladPolozka::whereColumn('mnozstvi_skladem', '<=', 'min_mnozstvi')
            ->where('min_mnozstvi', '>', 0)->count();

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
                ->description(now()->translatedFormat('F Y'))
                ->color('info')
                ->icon('heroicon-o-calendar-days')
                ->url(Kalendar::getUrl()),

            Stat::make('Sklad pod minimem', $podMinimem)
                ->description('položky k doobjednání')
                ->color($podMinimem > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-exclamation-triangle')
                ->url(SkladPolozkaResource::getUrl('index', ['tableFilters' => ['pod_minimem' => ['isActive' => true]]])),
        ];
    }
}
