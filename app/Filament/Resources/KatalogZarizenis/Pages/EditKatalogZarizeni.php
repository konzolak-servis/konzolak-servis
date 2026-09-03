<?php

namespace App\Filament\Resources\KatalogZarizenis\Pages;

use App\Filament\Resources\KatalogZarizenis\KatalogZarizeniResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKatalogZarizeni extends EditRecord
{
    protected static string $resource = KatalogZarizeniResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
