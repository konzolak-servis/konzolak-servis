<?php

namespace App\Filament\Resources\Fakturas\Pages;

use App\Filament\Resources\Fakturas\FakturaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateFaktura extends CreateRecord
{
    protected static string $resource = FakturaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nahled')
                ->label('Náhled PDF')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->action(function () {
                    session()->put('nahled_faktura', $this->data ?? []);
                    $this->js("window.open('" . route('tisk.nahled.faktura') . "', '_blank')");
                }),
        ];
    }
}
