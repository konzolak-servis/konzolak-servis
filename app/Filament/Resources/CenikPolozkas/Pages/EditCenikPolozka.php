<?php

namespace App\Filament\Resources\CenikPolozkas\Pages;

use App\Filament\Resources\CenikPolozkas\CenikPolozkaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCenikPolozka extends EditRecord
{
    protected static string $resource = CenikPolozkaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
