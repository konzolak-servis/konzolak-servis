<?php

namespace App\Filament\Resources\Zakazniks\Pages;

use App\Filament\Resources\Zakazniks\ZakaznikResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListZakazniks extends ListRecords
{
    protected static string $resource = ZakaznikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
