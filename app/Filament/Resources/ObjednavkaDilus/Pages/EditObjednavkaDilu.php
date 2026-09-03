<?php

namespace App\Filament\Resources\ObjednavkaDilus\Pages;

use App\Filament\Resources\ObjednavkaDilus\ObjednavkaDiluResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObjednavkaDilu extends EditRecord
{
    protected static string $resource = ObjednavkaDiluResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
