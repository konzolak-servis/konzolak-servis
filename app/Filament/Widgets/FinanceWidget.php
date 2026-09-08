<?php

namespace App\Filament\Widgets;

use App\Models\PenezniDenik;
use Filament\Widgets\ChartWidget;

class FinanceWidget extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '240px';

    public function getHeading(): ?string
    {
        return 'Příjmy, výdaje a zisk – ' . now()->translatedFormat('F Y');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $zaklad = PenezniDenik::whereYear('datum', now()->year)
            ->whereMonth('datum', now()->month);

        $prijem = (float) (clone $zaklad)->where('typ', 'prijem')->sum('castka');
        $vydej = (float) (clone $zaklad)->where('typ', 'vydej')->sum('castka');
        $zisk = $prijem - $vydej;

        return [
            'datasets' => [[
                'label' => 'Kč',
                'data' => [round($prijem), round($vydej), round($zisk)],
                'backgroundColor' => ['#5cd08f', '#ff8a80', $zisk >= 0 ? '#d1a13a' : '#b3261e'],
                'borderWidth' => 0,
            ]],
            'labels' => ['Příjem', 'Výdej', 'Čistý zisk'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }
}
