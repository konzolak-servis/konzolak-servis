<?php

namespace App\Filament\Widgets;

use App\Models\NavstevaWebu;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/** Návštěvnost veřejného webu konzolak.com (z počítadla PocitadloNavstev). */
class NavstevnostWebuWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    // pod číselnými dlaždicemi zakázek
    protected static ?int $sort = 1;

    protected ?string $heading = 'Návštěvnost webu';

    protected function getStats(): array
    {
        $dnes = now()->toDateString();
        $od = fn (int $dni) => now()->subDays($dni - 1)->toDateString();
        $cislo = fn (int $v) => number_format($v, 0, ',', ' ');

        $denne = NavstevaWebu::where('den', '>=', $od(14))
            ->selectRaw('den, COUNT(*) as navstevnici, SUM(zobrazeni) as zobrazeni')
            ->groupBy('den')->get()->keyBy(fn ($r) => $r->den->toDateString());

        $graf = [];
        for ($i = 13; $i >= 0; $i--) {
            $graf[] = (int) ($denne[now()->subDays($i)->toDateString()]->navstevnici ?? 0);
        }

        $dnesNavstevnici = (int) ($denne[$dnes]->navstevnici ?? 0);
        $dnesZobrazeni = (int) ($denne[$dnes]->zobrazeni ?? 0);
        $tyden = NavstevaWebu::where('den', '>=', $od(7))->count();
        $mesic = NavstevaWebu::where('den', '>=', $od(30))->count();
        $celkem = NavstevaWebu::count();
        $celkemZobrazeni = (int) NavstevaWebu::sum('zobrazeni');
        $odkdy = NavstevaWebu::min('den');

        return [
            Stat::make('Návštěvníci dnes', $cislo($dnesNavstevnici))
                ->description($cislo($dnesZobrazeni) . ' zobrazení stránek')
                ->color($dnesNavstevnici > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-eye')
                ->chart($graf),

            Stat::make('Posledních 7 dní', $cislo($tyden))
                ->description('unikátních návštěv')
                ->color('info')
                ->icon('heroicon-o-calendar'),

            Stat::make('Posledních 30 dní', $cislo($mesic))
                ->description('unikátních návštěv')
                ->color('info')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Celkem', $cislo($celkem))
                ->description($cislo($celkemZobrazeni) . ' zobrazení'
                    . ($odkdy ? ' · od ' . Carbon::parse($odkdy)->format('j. n. Y') : ''))
                ->color('warning')
                ->icon('heroicon-o-globe-alt'),
        ];
    }
}
