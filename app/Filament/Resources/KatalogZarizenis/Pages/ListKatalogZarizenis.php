<?php

namespace App\Filament\Resources\KatalogZarizenis\Pages;

use App\Filament\Resources\KatalogZarizenis\KatalogZarizeniResource;
use App\Filament\Resources\KatalogZarizenis\Schemas\KatalogZarizeniForm;
use App\Models\KatalogZarizeni;
use App\Support\Platformy;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ListKatalogZarizenis extends ListRecords
{
    protected static string $resource = KatalogZarizeniResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // rychlé přidání přímo v modálu, bez odchodu ze seznamu
            CreateAction::make()
                ->label('Přidat model')
                ->modalHeading('Nový model do katalogu')
                ->schema(fn (Schema $schema) => KatalogZarizeniForm::configure($schema))
                ->mutateDataUsing(function (array $data) {
                    $data['poradi'] ??= (int) (KatalogZarizeni::max('poradi') + 1);

                    return $data;
                }),
        ];
    }

    /** Platformy jako záložky nad seznamem (v pořadí číselníku). */
    public function getTabs(): array
    {
        $pocty = KatalogZarizeni::query()
            ->selectRaw('kategorie, COUNT(*) as pocet')
            ->groupBy('kategorie')
            ->pluck('pocet', 'kategorie');

        $tabs = ['vse' => Tab::make('Vše')->badge(KatalogZarizeni::count())];

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
