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
            Action::make('vyridit')
                ->label('Vyřídit a tisk dokladu')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->button()
                ->visible(fn () => ! $this->record->vyrizeno)
                ->requiresConfirmation()
                ->modalDescription(fn () => $this->record->typ === 'vykup'
                    ? 'Zapíše výdej peněz a naskladní kus do bazaru. Otevře doklad o výkupu.'
                    : 'Zapíše příjem peněz a odečte kus ze skladu. Otevře doklad o prodeji.')
                ->action(function () {
                    $this->record->vyridit();

                    return redirect(route('tisk.obchod', $this->record));
                }),

            Action::make('doklad')
                ->label('Doklad (PDF)')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('tisk.obchod', $this->record))
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }
}
