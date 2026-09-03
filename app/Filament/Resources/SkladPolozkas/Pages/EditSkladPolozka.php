<?php

namespace App\Filament\Resources\SkladPolozkas\Pages;

use App\Filament\Resources\SkladPolozkas\SkladPolozkaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSkladPolozka extends EditRecord
{
    protected static string $resource = SkladPolozkaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
