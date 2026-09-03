<?php

namespace App\Filament\Resources\Fakturas\Pages;

use App\Filament\Resources\Fakturas\FakturaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFakturas extends ListRecords
{
    protected static string $resource = FakturaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
