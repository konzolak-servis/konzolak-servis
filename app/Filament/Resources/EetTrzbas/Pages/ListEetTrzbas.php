<?php

namespace App\Filament\Resources\EetTrzbas\Pages;

use App\Filament\Resources\EetTrzbas\EetTrzbaResource;
use App\Models\Firma;
use Filament\Resources\Pages\ListRecords;

class ListEetTrzbas extends ListRecords
{
    protected static string $resource = EetTrzbaResource::class;

    public function getSubheading(): ?string
    {
        $f = Firma::get();
        if (! \App\Support\Eet\Eet::zapnuto()) {
            return 'EET je vypnuté nebo nenastavené – Nastavení firmy → EET 2.0.';
        }

        return 'Odesílání zapnuto · ' . ($f->eet_prostredi === 'produkce' ? 'ostré prostředí' : 'TESTOVACÍ prostředí')
            . ($f->eet_overeni ? ' · ověřovací režim (nic se neeviduje)' : '')
            . ' · DIČ ' . $f->eet_dic . ' · provozovna ' . $f->eet_id_jednotky;
    }
}
