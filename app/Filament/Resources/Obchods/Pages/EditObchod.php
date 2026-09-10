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
                ->url(fn () => route('tisk.obchod', $this->record))
                ->openUrlInNewTab(),

            Action::make('doklad_prodej')
                ->label('Doklad o prodeji (PDF)')
                ->icon('heroicon-o-document-check')
                ->visible(fn () => (bool) $this->record->prodano)
                ->url(fn () => route('tisk.obchod', ['obchod' => $this->record, 'typ' => 'prodej']))
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }
}
