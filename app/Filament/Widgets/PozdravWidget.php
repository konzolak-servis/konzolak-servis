<?php

namespace App\Filament\Widgets;

use App\Support\Pocasi;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class PozdravWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.pozdrav-widget';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $h = (int) now()->format('G');
        $pozdrav = match (true) {
            $h < 9 => 'Dobré ráno',
            $h < 12 => 'Dobré dopoledne',
            $h < 18 => 'Dobré odpoledne',
            default => 'Dobrý večer',
        };

        // ruční oslovení (u uživatele nebo firmy) má přednost; jinak jméno uživatele.
        // Vždy projde 5. pádem – funguje pro „Terezka" i pro už vyskloňované „Terezko".
        $zaklad = trim((string) (auth()->user()->osloveni ?? ''))
            ?: trim((string) \App\Models\Firma::get()->osloveni)
            ?: (string) (auth()->user()->name ?? '');
        $osloveni = \App\Support\Vokativ::osloveni($zaklad);

        Carbon::setLocale('cs');

        return [
            'pozdrav' => $pozdrav,
            'jmeno' => $osloveni,
            'datum' => now()->translatedFormat('l j. F Y'),
            'pocasi' => Pocasi::aktualni(),
        ];
    }
}
