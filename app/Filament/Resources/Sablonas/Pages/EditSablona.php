<?php

namespace App\Filament\Resources\Sablonas\Pages;

use App\Filament\Resources\Sablonas\SablonaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSablona extends EditRecord
{
    protected static string $resource = SablonaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
