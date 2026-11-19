<?php

namespace App\Filament\Resources\Zapujckas\Pages;

use App\Filament\Resources\Zapujckas\ZapujckaResource;
use App\Models\ZapujcniZarizeni;
use Filament\Resources\Pages\CreateRecord;

class CreateZapujcka extends CreateRecord
{
    protected static string $resource = ZapujckaResource::class;

    /** Tlačítko „Zapůjčit" ze seznamu zařízení k zápůjčce předvyplní zařízení a kauci. */
    protected function fillForm(): void
    {
        parent::fillForm();

        $d = ($id = request()->query('zarizeni')) ? ZapujcniZarizeni::find($id) : null;
        if (! $d) {
            return;
        }

        $this->form->fill(array_merge($this->form->getRawState(), [
            'kauce' => (float) $d->kauce,
            'polozky' => [[
                'zapujcni_zarizeni_id' => $d->id,
                'nazev' => $d->nazev,
                'seriove_cislo' => $d->seriove_cislo,
                'prislusenstvi' => $d->prislusenstvi,
                'stav_pri_zapujceni' => $d->stav_popis,
                'hodnota' => $d->hodnota,
            ]],
        ]));
    }

    protected function getRedirectUrl(): string
    {
        return ZapujckaResource::getUrl('edit', ['record' => $this->record]);
    }
}
