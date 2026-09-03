<?php

namespace App\Filament\Resources\Obchods\Pages;

use App\Filament\Resources\Obchods\ObchodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListObchods extends ListRecords
{
    protected static string $resource = ObchodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
