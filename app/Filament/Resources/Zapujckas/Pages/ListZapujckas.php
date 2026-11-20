<?php

namespace App\Filament\Resources\Zapujckas\Pages;

use App\Filament\Resources\Zapujckas\ZapujckaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListZapujckas extends ListRecords
{
    protected static string $resource = ZapujckaResource::class;

    /** Kolik kaucí právě držím (kauce není tržba – při kontrole pokladny). */
    public function getSubheading(): ?string
    {
        $bezici = \App\Models\Zapujcka::where('stav', 'zapujceno');
        $soucet = (float) (clone $bezici)->sum('kauce');
        $pocet = (clone $bezici)->count();
        $hotove = (float) (clone $bezici)->where('kauce_forma', 'hotově')->sum('kauce');

        return $pocet
            ? 'Kauce u mě: ' . number_format($soucet, 0, ',', ' ') . ' Kč (' . $pocet . ' ' . ($pocet === 1 ? 'zápůjčka' : ($pocet < 5 ? 'zápůjčky' : 'zápůjček')) . ')'
                . ($hotove > 0 && $hotove < $soucet ? ' · z toho hotově ' . number_format($hotove, 0, ',', ' ') . ' Kč' : '')
            : 'Žádné zařízení není zapůjčené, nedržíš žádnou kauci.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nová zápůjčka')];
    }
}
