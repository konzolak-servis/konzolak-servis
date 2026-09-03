<?php

namespace App\Filament\Resources\Zakazniks\Pages;

use App\Filament\Resources\Zakazniks\ZakaznikResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditZakaznik extends EditRecord
{
    protected static string $resource = ZakaznikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
