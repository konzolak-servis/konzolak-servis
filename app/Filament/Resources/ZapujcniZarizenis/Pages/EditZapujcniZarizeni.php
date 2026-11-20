<?php

namespace App\Filament\Resources\ZapujcniZarizenis\Pages;

use App\Filament\Resources\ZapujcniZarizenis\ZapujcniZarizeniResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditZapujcniZarizeni extends EditRecord
{
    protected static string $resource = ZapujcniZarizeniResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('stitek')->label('Štítek (PDF)')->icon('heroicon-o-tag')->color('primary')
                ->url(fn () => route('tisk.zapujcni.stitek', ['zarizeni' => $this->record]))->openUrlInNewTab(),
            Action::make('stitek_png')->label('Štítek PNG')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->url(fn () => route('tisk.zapujcni.stitek-png', ['zarizeni' => $this->record,
                    'soubor' => 'Stitek-zapujcka-' . $this->record->id . '.png'])),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ZapujcniZarizeniResource::getUrl('index');
    }
}