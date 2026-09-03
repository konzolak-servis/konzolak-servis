<?php

namespace App\Filament\Resources\Nabidkas\Pages;

use App\Filament\Resources\Nabidkas\NabidkaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNabidkas extends ListRecords
{
    protected static string $resource = NabidkaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
