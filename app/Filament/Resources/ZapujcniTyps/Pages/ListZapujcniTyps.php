<?php

namespace App\Filament\Resources\ZapujcniTyps\Pages;

use App\Filament\Resources\ZapujcniTyps\ZapujcniTypResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListZapujcniTyps extends ListRecords
{
    protected static string $resource = ZapujcniTypResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Přidat typ')];
    }
}
