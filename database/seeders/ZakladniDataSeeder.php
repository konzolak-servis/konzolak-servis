<?php

namespace Database\Seeders;

use App\Models\CenikPolozka;
use App\Models\Firma;
use Illuminate\Database\Seeder;

class ZakladniDataSeeder extends Seeder
{
    public function run(): void
    {
        // --- Firma (hlavička dokladů) ---
        Firma::query()->updateOrCreate(['id' => 1], [
            'nazev' => 'Konzolák Zlín',
            'ico' => '74689428',
            'ulice' => 'Na Honech I 4905',
            'mesto' => 'Zlín',
            'psc' => '760 05',
            'telefon' => '773 001 488',
            'email' => null, // doplní se po spuštění webu
            'cislo_uctu' => '197127370/0600',
            'platce_dph' => false,
            'splatnost_dni' => 14,
            'zaruka_mesice' => 3,
            'pravni_text_servisni_list' => 'Zákazník bere na vědomí, že veškeré výše uvedené informace jsou uváděny jako předběžné a během servisního zásahu se mohou změnit. Prodávající neručí za možné vady HW a SW, které se projeví během servisního zásahu nebo následně a neprokazatelně s ním souvisí; to se týká zejména použitých zařízení. Prodávající nepřebírá odpovědnost za data ponechaná na médiích. Zálohování dat je v plné odpovědnosti zákazníka. Opravený výrobek se vydává na základě předložení originálu tohoto potvrzení.',
            'pravni_text_protokol' => 'Zákazník tímto potvrzuje, že zboží přebírá ve stavu, v jakém jej do opravy předal, včetně kompletního příslušenství a vyměněných vadných součástí (neplatí u záruční opravy). Výrobek byl po opravě zákazníkovi (dle možností) předveden. Záruka na provedenou opravu je 3 měsíce.',
            'pravni_text_faktura' => 'Faktura slouží zároveň jako daňový doklad. '
                . 'Při platbě uvádějte variabilní symbol.',
            'pravni_text_nabidka' => 'Výrobek byl zákazníkovi (dle možností) předveden. Jedná se o nové díly. Záruka dle prodeje.',
        ]);

        // --- Ceník servisních úkonů (hrubý náčrt, plně editovatelný) ---
        $cenik = [
            ['Ovladač', 'Diagnostika a čištění ovladače', 400],
            ['Ovladač', 'Oprava potenciometru (driftování) / 1 ks', 250],
            ['Ovladač', 'Oprava tlačítek (L1, L2, R1, R2, LB, LT, RB, RT)', 500],
            ['Ovladač', 'Výměna integrované baterie', 600],
            ['Ovladač', 'Výměna kloboučků', 500],
            ['Obecné', 'Update / obnovení software', 300],
            ['Obecné', 'Čištění konzole, diagnostika a přepastování', 500],
            ['Obecné', 'Oprava HDMI konektoru', 1200],
            ['Obecné', 'Oprava interního ventilátoru', 800],
            ['PS4', 'Výměna HDD 1 TB včetně instalace OS – PS4', 1600],
            ['PS4', 'Výměna HDD 2 TB včetně instalace OS – PS4', 2600],
            ['PS4', 'Výměna SSD 1 TB včetně instalace OS – PS4', 2600],
            ['PS4', 'Výměna SSD 2 TB včetně instalace OS – PS4', 3600],
            ['PS5', 'Rozšíření interní paměti PS5 – 512 GB', 3500],
            ['PS5', 'Rozšíření interní paměti PS5 – 1 TB', 4500],
            ['PS5', 'Rozšíření interní paměti PS5 – 2 TB', 8500],
            ['Nintendo Switch', 'Oprava displeje Nintendo Switch / Lite', 2000],
            ['Nintendo Switch', 'Oprava USB-C konektoru Nintendo Switch / Lite', 1000],
            ['Nintendo Switch', 'Oprava interního ventilátoru Nintendo Switch / Lite', 800],
            ['Nintendo Switch', 'Oprava nabíjecích kolejnic u Joyconů a konzole', 800],
            ['Nintendo Switch', 'Oprava čtečky herních karet či sluchátek', 1600],
        ];

        foreach ($cenik as $i => [$kat, $nazev, $cena]) {
            CenikPolozka::query()->updateOrCreate(
                ['nazev' => $nazev],
                ['kategorie' => $kat, 'cena' => $cena, 'aktivni' => true, 'poradi' => $i],
            );
        }
    }
}
