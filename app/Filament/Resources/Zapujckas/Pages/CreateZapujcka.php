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

        // „Půjčit náhradní" ze zakázky: zákazník, zakázka a zdarma po dobu opravy
        if (($zid = request()->query('zakazka')) && ($zak = \App\Models\Zakazka::find($zid))) {
            $this->form->fill(array_merge($this->form->getRawState(), [
                'zakazka_id' => $zak->id,
                'zakaznik_id' => $zak->zakaznik_id,
                'zdarma' => true,
            ]));
        }

        $d = ($id = request()->query('zarizeni')) ? ZapujcniZarizeni::find($id) : null;
        if (! $d) {
            return;
        }

        $this->form->fill(array_merge($this->form->getRawState(), [
            'kauce' => (float) $d->kauce,
            'denni_sazba' => (float) $d->denni_sazba,

            'vikend_cely' => $d->vikend_cely,
            'polozky' => [[
                'zapujcni_zarizeni_id' => $d->id,
                'nazev' => $d->nazev,
                'seriove_cislo' => $d->seriove_cislo,
                'prislusenstvi' => $d->prislusenstvi,
                'hry' => $d->hry,
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
