<?php

namespace App\Filament\Resources\Sablonas\Pages;

use App\Filament\Resources\Sablonas\SablonaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSablonas extends ListRecords
{
    protected static string $resource = SablonaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
