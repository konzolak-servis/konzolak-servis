<?php

namespace App\Filament\Resources\ProdejDilus\Pages;

use App\Filament\Resources\ProdejDilus\ProdejDiluResource;
use App\Models\SkladPolozka;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateProdejDilu extends CreateRecord
{
    protected static string $resource = ProdejDiluResource::class;

    protected static ?string $title = 'Prodej dílů zákazníkovi';

    /** Tlačítko „Prodat" ve skladu předvyplní díl. */
    protected function fillForm(): void
    {
        parent::fillForm();
        $s = ($id = request()->query('polozka')) ? SkladPolozka::find($id) : null;
        if ($s) {
            $this->form->fill(array_merge($this->form->getRawState(), [
                'polozky' => [['sklad_polozka_id' => $s->id, 'nazev' => $s->nazev, 'mnozstvi' => 1, 'cena_ks' => null]],
            ]));
        }
    }

    /** Po uložení: výdej ze skladu, příjem do deníku (→ EET). */
    protected function afterCreate(): void
    {
        $this->record->dokoncit();
    }

    protected function getCreatedNotification(): ?Notification
    {
        $p = $this->record->fresh();

        return Notification::make()->success()
            ->title('Prodáno za ' . number_format((float) $p->celkem, 0, ',', ' ') . ' Kč')
            ->body('Díly odepsány ze skladu, příjem zapsán do peněžního deníku.')
            ->actions([Action::make('doklad')->label('Doklad o prodeji (PDF)')->button()->url($p->tiskUrl(), shouldOpenInNewTab: true)])
            ->persistent();
    }

    protected function getRedirectUrl(): string
    {
        return ProdejDiluResource::getUrl('index');
    }
}
