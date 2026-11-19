<?php

namespace App\Filament\Resources\Zapujckas\Pages;

use App\Filament\Resources\Zapujckas\ZapujckaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateZapujcka extends CreateRecord
{
    protected static string $resource = ZapujckaResource::class;

    protected function getRedirectUrl(): string
    {
        return ZapujckaResource::getUrl('edit', ['record' => $this->record]);
    }
}
