<?php

namespace App\Filament\Resources\Obchods\Pages;

use App\Filament\Resources\Obchods\ObchodResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateObchod extends CreateRecord
{
    protected static string $resource = ObchodResource::class;

    /** Po vytvoření zůstat na kartě položky a nabídnout doklad v novém okně. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Bazarová položka založena')
            ->success()
            ->actions([
                Action::make('doklad_vykup')
                    ->label('Otevřít doklad o výkupu (PDF)')
                    ->icon('heroicon-o-document-text')
                    ->url(route('tisk.obchod', $this->record), shouldOpenInNewTab: true)
                    ->button(),
            ])
            ->persistent()
            ->send();
    }
}
