<?php

namespace App\Filament\Resources\Zapujckas\Pages;

use App\Filament\Resources\Zapujckas\ZapujckaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListZapujckas extends ListRecords
{
    protected static string $resource = ZapujckaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nová zápůjčka')];
    }
}
