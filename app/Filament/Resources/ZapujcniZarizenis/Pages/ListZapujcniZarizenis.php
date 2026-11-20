<?php

namespace App\Filament\Resources\ZapujcniZarizenis\Pages;

use App\Filament\Resources\ZapujcniZarizenis\ZapujcniZarizeniResource;
use App\Models\ZapujcniZarizeni;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListZapujcniZarizenis extends ListRecords
{
    protected static string $resource = ZapujcniZarizeniResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('stitky_vse')->label('Štítky všech zařízení')->icon('heroicon-o-tag')->color('gray')
                ->visible(fn () => ZapujcniZarizeni::where('aktivni', true)->exists())
                ->url(fn () => route('tisk.zapujcni.stitek', ['ids' => ZapujcniZarizeni::where('aktivni', true)->pluck('id')->implode(',')]))
                ->openUrlInNewTab(),
            CreateAction::make()->label('Přidat zařízení'),
        ];
    }
}