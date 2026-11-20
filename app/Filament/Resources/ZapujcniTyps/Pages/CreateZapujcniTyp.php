<?php

namespace App\Filament\Resources\ZapujcniTyps\Pages;

use App\Filament\Resources\ZapujcniTyps\ZapujcniTypResource;
use Filament\Resources\Pages\CreateRecord;

class CreateZapujcniTyp extends CreateRecord
{
    protected static string $resource = ZapujcniTypResource::class;

    protected function getRedirectUrl(): string
    {
        return ZapujcniTypResource::getUrl('index');
    }
}
