<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Zakazkas\ZakazkaResource;
use App\Models\Zakazka;
use Filament\Widgets\Widget;

class UkolyWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.ukoly-widget';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'kVydani' => Zakazka::with(['zakaznik', 'zarizeni'])
                ->where('stav', 'hotovo')
                ->orderBy('datum_vyrizeni')
                ->get()
                ->map(fn (Zakazka $z) => [
                    'cislo' => $z->cislo,
                    'kdo' => $z->zakaznik?->nazev,
                    'co' => $z->zarizeni?->oznaceni,
                    'url' => ZakazkaResource::getUrl('edit', ['record' => $z]),
                ]),

            'cekaNaDil' => Zakazka::with(['zakaznik', 'zarizeni'])
                ->where('stav', 'ceka_na_dil')
                ->orderBy('datum_prijeti')
                ->get()
                ->map(fn (Zakazka $z) => [
                    'cislo' => $z->cislo,
                    'kdo' => $z->zakaznik?->nazev,
                    'co' => $z->zarizeni?->oznaceni,
                    'dil_objednany' => $z->dil_objednany,
                    'dil_info' => $z->dil_info,
                    'potreba' => $z->interni_potreba,
                    'url' => ZakazkaResource::getUrl('edit', ['record' => $z]),
                ]),
        ];
    }
}
