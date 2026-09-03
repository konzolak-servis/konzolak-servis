<?php

namespace App\Filament\Resources\Obchods\Pages;

use App\Filament\Resources\Obchods\ObchodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateObchod extends CreateRecord
{
    protected static string $resource = ObchodResource::class;

    /** Po vytvoření rovnou otevřít doklad k tisku. */
    protected function getRedirectUrl(): string
    {
        return route('tisk.obchod', $this->record);
    }
}
