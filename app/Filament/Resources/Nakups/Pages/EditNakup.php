<?php

namespace App\Filament\Resources\Nakups\Pages;

use App\Filament\Resources\Nakups\NakupResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditNakup extends EditRecord
{
    protected static string $resource = NakupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('naskladnit')
                ->label('Naskladnit')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => ! $this->record->naskladneno && $this->record->polozky()->exists())
                ->requiresConfirmation()
                ->modalHeading(fn () => 'Naskladnit nákup ' . $this->record->cislo)
                ->modalDescription('Přidá kusy na sklad v ceně podle položky (bez poštovného), přepočítá vážený průměr a do peněžního deníku zapíše výdaj = položky + poštovné. Nelze vzít zpět.')
                ->modalSubmitActionLabel('Naskladnit')
                ->action(function () {
                    // neuložené změny formuláře (např. poštovné) nejdřív uložit
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    $this->record->refresh()->naskladnit();
                    $this->refreshFormData(['naskladneno', 'datum_naskladneni', 'celkem']);

                    Notification::make()->title('Nákup ' . $this->record->cislo . ' naskladněn')
                        ->body('Celkem ' . number_format((float) $this->record->celkem, 0, ',', ' ') . ' Kč zapsáno do peněžního deníku.')
                        ->success()->send();
                }),
            DeleteAction::make(),
        ];
    }
}
