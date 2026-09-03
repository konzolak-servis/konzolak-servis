<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Fakturas\FakturaResource;
use App\Filament\Resources\ObjednavkaDilus\ObjednavkaDiluResource;
use App\Filament\Resources\Pristups\PristupResource;
use App\Models\Faktura;
use App\Models\ObjednavkaDilu;
use App\Models\Pristup;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class SplatnostiWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.splatnosti-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $dnes = Carbon::today();
        $polozky = [];

        // Přístupy / služby s blížící se obnovou
        foreach (Pristup::where('aktivni', true)->whereNotNull('platnost_do')->get() as $p) {
            if ($p->jeNaSpadnuti()) {
                $polozky[] = [
                    'typ' => Pristup::KATEGORIE[$p->kategorie] ?? 'Přístup',
                    'nazev' => $p->nazev,
                    'datum' => $p->platnost_do,
                    'dni' => $p->dniDoKonce(),
                    'castka' => $p->castka,
                    'url' => PristupResource::getUrl('edit', ['record' => $p]),
                ];
            }
        }

        // Neuhrazené vydané faktury
        foreach (Faktura::where('uhrazeno', false)->whereNotNull('datum_splatnosti')->get() as $f) {
            $dni = (int) $dnes->diffInDays($f->datum_splatnosti, false);
            if ($dni <= 14) {
                $polozky[] = [
                    'typ' => 'Faktura – neuhrazená',
                    'nazev' => $f->cislo . ($f->zakaznik ? ' · ' . $f->zakaznik->nazev : ''),
                    'datum' => $f->datum_splatnosti,
                    'dni' => $dni,
                    'castka' => $f->celkem,
                    'url' => FakturaResource::getUrl('edit', ['record' => $f]),
                ];
            }
        }

        // Objednávky dílů po termínu doručení
        foreach (ObjednavkaDilu::where('stav', 'objednano')->whereNotNull('ocekavane_doruceni')->get() as $o) {
            $dni = (int) $dnes->diffInDays($o->ocekavane_doruceni, false);
            if ($dni <= 3) {
                $polozky[] = [
                    'typ' => 'Objednaný díl',
                    'nazev' => $o->cislo . ' · ' . $o->nazev_dilu,
                    'datum' => $o->ocekavane_doruceni,
                    'dni' => $dni,
                    'castka' => $o->cena_odhad,
                    'url' => ObjednavkaDiluResource::getUrl('edit', ['record' => $o]),
                ];
            }
        }

        usort($polozky, fn ($a, $b) => $a['dni'] <=> $b['dni']);

        return ['polozky' => $polozky];
    }
}
