<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Zpravas\ZpravaResource;
use App\Models\Zprava;
use Filament\Widgets\Widget;

class PostaWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.posta-widget';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $zpravy = Zprava::query()
            ->where('smer', 'in')
            ->where('spam', false)
            ->with('zakazka')
            ->orderByRaw('precteno_at IS NOT NULL')      // nepřečtené první
            ->orderByDesc('datum')
            ->limit(6)
            ->get()
            ->map(fn (Zprava $z) => [
                'id' => $z->id,
                'od' => $z->od_jmeno ?: $z->od,
                'predmet' => $z->predmet,
                'nahled' => $z->nahled(90),
                'datum' => $z->datum,
                'precteno' => $z->jePrectena(),
                'zakazka' => $z->zakazka?->cislo,
                'url' => ZpravaResource::getUrl('view', ['record' => $z->id]),
            ])
            ->all();

        return [
            'zpravy' => $zpravy,
            'neprectene' => Zprava::neprectene()->count(),
            'celkemIn' => Zprava::where('smer', 'in')->where('spam', false)->count(),
            'vseUrl' => ZpravaResource::getUrl(),
        ];
    }
}
