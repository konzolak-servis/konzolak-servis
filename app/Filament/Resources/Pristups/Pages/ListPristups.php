<?php

namespace App\Filament\Resources\Pristups\Pages;

use App\Filament\Resources\Pristups\PristupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPristups extends ListRecords
{
    protected static string $resource = PristupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
