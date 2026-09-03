<?php

namespace App\Filament\Resources\Nabidkas\Pages;

use App\Filament\Resources\Nabidkas\NabidkaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateNabidka extends CreateRecord
{
    protected static string $resource = NabidkaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nahled')
                ->label('Náhled PDF')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->action(function () {
                    session()->put('nahled_nabidka', $this->data ?? []);
                    $this->js("window.open('" . route('tisk.nahled.nabidka') . "', '_blank')");
                }),
        ];
    }
}
