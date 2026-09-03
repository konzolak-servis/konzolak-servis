<?php

namespace App\Filament\Resources\KatalogZarizenis\Pages;

use App\Filament\Resources\KatalogZarizenis\KatalogZarizeniResource;
use App\Filament\Resources\KatalogZarizenis\Schemas\KatalogZarizeniForm;
use App\Models\KatalogZarizeni;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;

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
}
