<?php

namespace App\Filament\Resources\ZapujcniZarizenis\Pages;

use App\Filament\Resources\ZapujcniZarizenis\ZapujcniZarizeniResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListZapujcniZarizenis extends ListRecords
{
    protected static string $resource = ZapujcniZarizeniResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Přidat zařízení')];
    }
}
