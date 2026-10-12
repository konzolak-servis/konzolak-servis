<?php

namespace App\Filament\Resources\Obchods\Pages;

use App\Filament\Resources\Obchods\ObchodResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObchod extends EditRecord
{
    protected static string $resource = ObchodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('doklad_vykup')
                ->label('Doklad o výkupu (PDF)')
                ->icon('heroicon-o-document-text')
                ->url(fn () => $this->record->tiskUrl())
                ->openUrlInNewTab(),
            Action::make('doklad_vykup_cb')
                ->label('Doklad o výkupu – tisk ČB (PDF)')
                ->icon('heroicon-o-printer')
                ->url(fn () => $this->record->tiskUrl(['cb' => 1]))
                ->openUrlInNewTab(),

            Action::make('doklad_prodej')
                ->label('Doklad o prodeji (PDF)')
                ->icon('heroicon-o-document-check')
                ->visible(fn () => (bool) $this->record->prodano)
                ->url(fn () => $this->record->tiskUrl(['typ' => 'prodej']))
                ->openUrlInNewTab(),
            Action::make('doklad_prodej_cb')
                ->label('Doklad o prodeji – tisk ČB (PDF)')
                ->icon('heroicon-o-printer')
                ->visible(fn () => (bool) $this->record->prodano)
                ->url(fn () => $this->record->tiskUrl(['typ' => 'prodej', 'cb' => 1]))
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }
}
