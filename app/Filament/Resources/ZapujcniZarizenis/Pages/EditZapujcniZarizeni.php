<?php

namespace App\Filament\Resources\ZapujcniZarizenis\Pages;

use App\Filament\Resources\ZapujcniZarizenis\ZapujcniZarizeniResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditZapujcniZarizeni extends EditRecord
{
    protected static string $resource = ZapujcniZarizeniResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return ZapujcniZarizeniResource::getUrl('index');
    }
}
