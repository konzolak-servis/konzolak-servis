<?php

namespace App\Filament\Resources\Zarizenis\Pages;

use App\Filament\Resources\Zarizenis\ZarizeniResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditZarizeni extends EditRecord
{
    protected static string $resource = ZarizeniResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
