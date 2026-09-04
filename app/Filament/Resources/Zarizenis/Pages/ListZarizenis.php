<?php

namespace App\Filament\Resources\Zarizenis\Pages;

use App\Filament\Resources\Zarizenis\ZarizeniResource;
use App\Models\Zarizeni;
use App\Support\Platformy;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListZarizenis extends ListRecords
{
    protected static string $resource = ZarizeniResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /** Platformy jako záložky nad seznamem (v pořadí číselníku). */
    public function getTabs(): array
    {
        $pocty = Zarizeni::query()
            ->selectRaw('kategorie, COUNT(*) as pocet')
            ->groupBy('kategorie')
            ->pluck('pocet', 'kategorie');

        $tabs = ['vse' => Tab::make('Vše')->badge(Zarizeni::count())];

        foreach (Platformy::HODNOTY as $klic => $popisek) {
            if (! isset($pocty[$klic])) {
                continue;
            }

            $tabs[$klic] = Tab::make($popisek)
                ->badge($pocty[$klic])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kategorie', $klic));
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'vse';
    }
}
