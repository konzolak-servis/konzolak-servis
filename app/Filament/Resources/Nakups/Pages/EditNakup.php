<?php

namespace App\Filament\Resources\Nakups\Pages;

use App\Filament\Resources\Nakups\NakupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNakup extends EditRecord
{
    protected static string $resource = NakupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
