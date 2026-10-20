<?php

namespace App\Filament\Resources\SkladPolozkas\Pages;

use App\Filament\Resources\SkladPolozkas\SkladPolozkaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSkladPolozka extends EditRecord
{
    protected static string $resource = SkladPolozkaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inventura')
                ->label('Inventura – upravit stav')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('gray')
                ->visible(fn () => ! $this->record->spotrebni)
                ->modalHeading('Inventura – skutečný stav na skladě')
                ->modalDescription('Rozdíl se zapíše jako pohyb v historii, takže stav a historie vždy sedí.')
                ->schema(fn () => [
                    TextInput::make('stav')->label('Skutečné množství na skladě')->numeric()->required()->minValue(0)
                        ->default((float) $this->record->mnozstvi_skladem),
                    TextInput::make('poznamka')->label('Důvod (nepovinné)')->placeholder('inventura, rozbitý kus, ztráta…'),
                ])
                ->action(function (array $data) {
                    $pohyb = $this->record->inventura((float) $data['stav'], $data['poznamka'] ?? null);
                    $this->refreshFormData(['mnozstvi_skladem']);
                    Notification::make()
                        ->title($pohyb ? 'Stav upraven (rozdíl ' . rtrim(rtrim(number_format((float) $pohyb->mnozstvi, 3, ',', ' '), '0'), ',') . ' ks)' : 'Stav se nezměnil')
                        ->success()->send();
                }),
            DeleteAction::make(),
        ];
    }
}
