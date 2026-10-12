<?php

use App\Models\CenikPolozka;
use Illuminate\Database\Migrations\Migration;

/**
 * Doplnění ceníku o další běžné opravy (inspirace rozsahem ceníku jiných servisů, formulace vlastní).
 * Ceny jsou VÝCHOZÍ NÁVRH k úpravě v administraci (Ceník). Položky, které už existují (podle názvu), se přeskočí.
 */
return new class extends Migration
{
    private function polozky(): array
    {
        // [kategorie, název, cena]
        return [
            // základní servis konzolí
            ['jine', 'Diagnostika závady (při provedení opravy zdarma)', 500],
            ['jine', 'Čištění konzole + tekutý kov', 900],
            ['jine', 'Instalace systému na původní disk včetně kontroly HDD/SSD', 600],

            // PS4
            ['ps4', 'Výměna napájecího zdroje PS4', 1600],
            ['ps4', 'Oprava optické mechaniky PS4 (FAT / Slim)', 1000],
            ['ps4', 'Oprava optické mechaniky PS4 Pro včetně čištění a přepastování', 1400],
            ['ps4', 'Výměna HDMI konektoru PS4 včetně čištění a přepastování', 1400],
            ['ps4', 'Výměna HDMI obrazového čipu PS4 včetně čištění a přepastování', 1600],

            // PS5
            ['ps5', 'Výměna napájecího zdroje PS5 včetně čištění a tekutého kovu', 2500],
            ['ps5', 'Výměna HDMI čipu PS5 včetně čištění a tekutého kovu', 2600],
            ['ps5', 'Oprava optické mechaniky PS5', 1200],

            // Xbox One
            ['xbox_one', 'Výměna napájecího zdroje Xbox One S', 1200],
            ['xbox_one', 'Výměna napájecího zdroje Xbox One X', 1500],
            ['xbox_one', 'Oprava optické mechaniky Xbox One', 1200],
            ['xbox_one', 'Výměna HDMI konektoru Xbox One včetně čištění a přepastování', 1400],
            ['xbox_one', 'Výměna HDMI obrazového čipu Xbox One včetně čištění a přepastování', 1600],

            // Xbox Series
            ['xbox_series', 'Výměna napájecího zdroje Xbox Series S', 1800],
            ['xbox_series', 'Výměna napájecího zdroje Xbox Series X', 2800],
            ['xbox_series', 'Oprava optické mechaniky Xbox Series', 1200],
            ['xbox_series', 'Výměna HDMI konektoru Xbox Series včetně čištění a přepastování', 2000],

            // ovladače (PlayStation, Xbox)
            ['ovladac', 'Oprava jedné osy analogové páčky (drift) včetně kalibrace', 300],
            ['ovladac', 'Oprava obou os analogové páčky včetně kalibrace', 400],
            ['ovladac', 'Výměna kompletní analogové páčky včetně kalibrace', 500],
            ['ovladac', 'Výměna obou analogových páček včetně kalibrace', 800],
            ['ovladac', 'Výměna páček za magnetické Hall', 800],
            ['ovladac', 'Výměna USB konektoru DualShock', 300],
            ['ovladac', 'Výměna USB konektoru DualSense', 500],
            ['ovladac', 'Oprava audio konektoru ovladače PlayStation', 400],
            ['ovladac', 'Oprava audio konektoru ovladače Xbox', 300],

            // PC / notebook
            ['notebook', 'Diagnostika závady PC / notebooku', 500],
            ['notebook', 'Obnovení systému s původní licencí', 500],
            ['notebook', 'Čištění + výměna teplovodivé pasty (od)', 700],
        ];
    }

    public function up(): void
    {
        $poradi = (int) CenikPolozka::max('poradi');

        foreach ($this->polozky() as [$kat, $nazev, $cena]) {
            if (CenikPolozka::where('nazev', $nazev)->exists()) {
                continue;
            }
            CenikPolozka::create([
                'kategorie' => $kat,
                'nazev' => $nazev,
                'cena' => $cena,
                'aktivni' => true,
                'na_web' => true,
                'poradi' => ++$poradi,
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->polozky() as [, $nazev]) {
            CenikPolozka::where('nazev', $nazev)->delete();
        }
    }
};
