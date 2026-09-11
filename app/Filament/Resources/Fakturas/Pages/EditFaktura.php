<?php

namespace App\Filament\Resources\Fakturas\Pages;

use App\Filament\Resources\Fakturas\FakturaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFaktura extends EditRecord
{
    protected static string $resource = FakturaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nahled')
                ->label('Náhled / tisk PDF')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->url(fn () => route('tisk.faktura', $this->record))
                ->openUrlInNewTab(),
            Action::make('nahled_cb')
                ->label('Tisk ČB (PDF)')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('tisk.faktura', ['faktura' => $this->record, 'cb' => 1]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
