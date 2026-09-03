<?php

namespace App\Filament\Resources\Pristups\Pages;

use App\Filament\Resources\Pristups\PristupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPristup extends EditRecord
{
    protected static string $resource = PristupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
