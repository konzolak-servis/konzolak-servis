<?php

namespace App\Filament\Resources\ProdejDilus\Pages;

use App\Filament\Resources\ProdejDilus\ProdejDiluResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProdejDilus extends ListRecords
{
    protected static string $resource = ProdejDiluResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nový prodej')];
    }
}
