<?php

namespace App\Filament\Resources\Zakazkas\Pages;

use App\Filament\Resources\Zakazkas\ZakazkaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateZakazka extends CreateRecord
{
    protected static string $resource = ZakazkaResource::class;
}
