<?php

namespace App\Filament\Resources\ZapujcniZarizenis\Pages;

use App\Filament\Resources\ZapujcniZarizenis\ZapujcniZarizeniResource;
use Filament\Resources\Pages\CreateRecord;

class CreateZapujcniZarizeni extends CreateRecord
{
    protected static string $resource = ZapujcniZarizeniResource::class;

    protected function getRedirectUrl(): string
    {
        return ZapujcniZarizeniResource::getUrl('index');
    }
}
