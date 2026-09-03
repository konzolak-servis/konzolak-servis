<?php

namespace App\Filament\Resources\ObjednavkaDilus\Pages;

use App\Filament\Resources\ObjednavkaDilus\ObjednavkaDiluResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListObjednavkaDilus extends ListRecords
{
    protected static string $resource = ObjednavkaDiluResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
