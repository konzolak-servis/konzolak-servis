<?php

namespace App\Filament\Resources\Nabidkas\Pages;

use App\Filament\Resources\Fakturas\FakturaResource;
use App\Filament\Resources\Nabidkas\NabidkaResource;
use App\Models\Faktura;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditNabidka extends EditRecord
{
    protected static string $resource = NabidkaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nahled')
                ->label('Náhled / tisk PDF')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->url(fn () => route('tisk.nabidka', $this->record))
                ->openUrlInNewTab(),

            Action::make('prevest_na_fakturu')
                ->label('Převést na fakturu')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Vytvoří fakturu s řádky z této nabídky (bez interních údajů).')
                ->action(function () {
                    $f = Faktura::create([
                        'zakaznik_id' => $this->record->zakaznik_id,
                        'forma_uhrady' => 'převodem',
                        'datum_vystaveni' => now()->toDateString(),
                    ]);

                    foreach ($this->record->polozky as $p) {
                        $f->polozky()->create([
                            'popis' => trim(($p->skupina ? $p->skupina . ' – ' : '') . $p->popis),
                            'mnozstvi' => $p->mnozstvi,
                            'cena' => $p->cena,
                        ]);
                    }

                    if ($this->record->zaloha > 0) {
                        $f->polozky()->create([
                            'popis' => 'Uhrazená záloha',
                            'mnozstvi' => 1,
                            'cena' => -1 * (float) $this->record->zaloha,
                        ]);
                    }

                    $this->record->update(['stav' => 'prijata']);

                    Notification::make()->title('Faktura ' . $f->cislo . ' vytvořena z nabídky')->success()->send();

                    return redirect(FakturaResource::getUrl('edit', ['record' => $f]));
                }),

            DeleteAction::make(),
        ];
    }
}
