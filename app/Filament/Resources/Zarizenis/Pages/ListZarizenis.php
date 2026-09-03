<?php

namespace App\Filament\Resources\Zarizenis\Pages;

use App\Filament\Resources\Zarizenis\ZarizeniResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListZarizenis extends ListRecords
{
    protected static string $resource = ZarizeniResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
