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
                ->modalDescription('Přidá kusy na sklad (poštovné se rozpočítá do ceny položek), přepočítá vážený průměr a do peněžního deníku zapíše výdaj = položky + poštovné.')
                ->modalSubmitActionLabel('Naskladnit')
                ->action(function () {
                    // neuložené změny formuláře (např. poštovné) nejdřív uložit
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    $this->record->refresh()->naskladnit();
                    Notification::make()->title('Nákup ' . $this->record->cislo . ' naskladněn')
                        ->body('Celkem ' . number_format((float) $this->record->celkem, 0, ',', ' ') . ' Kč zapsáno do peněžního deníku.')
                        ->success()->send();

                    // znovu načíst stránku, aby se zamkly položky
                    $this->redirect(NakupResource::getUrl('edit', ['record' => $this->record]));
                }),
            Action::make('upravit_naskladneny')
                ->label('Upravit nákup')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn () => (bool) $this->record->naskladneno)
                ->requiresConfirmation()
                ->modalHeading(fn () => 'Upravit naskladněný nákup ' . $this->record->cislo)
                ->modalDescription('Naskladnění se vrátí zpět: kusy se odeberou ze skladu (průměrná cena se vrátí na stav před nákupem) a smaže se výdaj v peněžním deníku. Pak můžeš položky libovolně upravit, přidat nebo smazat a znovu kliknout na „Naskladnit".')
                ->modalSubmitActionLabel('Vrátit naskladnění a upravit')
                ->action(function () {
                    $varovani = $this->record->zrusitNaskladneni();

                    Notification::make()->title('Nákup ' . $this->record->cislo . ' lze upravit')
                        ->body('Po úpravách klikni na „Naskladnit".' . ($varovani ? "\n\nPozor – " . implode('; ', $varovani) : ''))
                        ->{$varovani ? 'warning' : 'success'}()->persistent((bool) $varovani)->send();

                    // znovu načíst stránku, aby se odemkly položky
                    $this->redirect(NakupResource::getUrl('edit', ['record' => $this->record]));
                }),
            DeleteAction::make(),
        ];
    }
}
