<?php

namespace App\Filament\Resources\PenezniDeniks\Pages;

use App\Filament\Resources\PenezniDeniks\PenezniDenikResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPenezniDenik extends EditRecord
{
    protected static string $resource = PenezniDenikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
