<?php

namespace App\Filament\Resources\ZapujcniTyps\Pages;

use App\Filament\Resources\ZapujcniTyps\ZapujcniTypResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditZapujcniTyp extends EditRecord
{
    protected static string $resource = ZapujcniTypResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return ZapujcniTypResource::getUrl('index');
    }
}
