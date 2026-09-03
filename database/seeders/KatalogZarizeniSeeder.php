<?php

namespace Database\Seeders;

use App\Models\KatalogZarizeni;
use Illuminate\Database\Seeder;

class KatalogZarizeniSeeder extends Seeder
{
    public function run(): void
    {
        // [kategorie, název, kód modelu]
        $data = [
            // ---- PlayStation 5 ----
            ['PS5', 'PlayStation 5 – Fat (disková)', 'CFI-10xx / 11xx / 12xx'],
            ['PS5', 'PlayStation 5 – Fat Digital Edition', 'CFI-10xxB / 11xxB / 12xxB'],
            ['PS5', 'PlayStation 5 Slim (disková)', 'CFI-20xx'],
            ['PS5', 'PlayStation 5 Slim Digital Edition', 'CFI-20xxB'],
            ['PS5', 'PlayStation 5 Pro', 'CFI-70xx'],

            // ---- PlayStation 4 ----
            ['PS4', 'PlayStation 4 – Fat', 'CUH-10xx / 11xx / 12xx'],
            ['PS4', 'PlayStation 4 Slim', 'CUH-20xx'],
            ['PS4', 'PlayStation 4 Pro', 'CUH-70xx'],

            // ---- PlayStation 3 ----
            ['PS3', 'PlayStation 3 – Fat', 'CECHxxx'],
            ['PS3', 'PlayStation 3 Slim', 'CECH-2xxx / 3xxx'],
            ['PS3', 'PlayStation 3 Super Slim', 'CECH-4xxx'],

            // ---- PlayStation handheld ----
            ['jine', 'PlayStation Portable (PSP)', 'PSP-1000 / 2000 / 3000'],
            ['jine', 'PlayStation Vita – OLED', 'PCH-1000'],
            ['jine', 'PlayStation Vita – Slim', 'PCH-2000'],
            ['jine', 'PlayStation Portal', 'CFIJ-18000'],

            // ---- Xbox ----
            ['Xbox', 'Xbox Series X', '1882'],
            ['Xbox', 'Xbox Series S', '1883'],
            ['Xbox', 'Xbox One – Fat', '1540 / 1520'],
            ['Xbox', 'Xbox One S', '1681'],
            ['Xbox', 'Xbox One S All-Digital', '1681'],
            ['Xbox', 'Xbox One X', '1787'],
            ['Xbox', 'Xbox 360 – Fat', ''],
            ['Xbox', 'Xbox 360 S (Slim)', '1439'],
            ['Xbox', 'Xbox 360 E', '1538'],
            ['Xbox', 'Xbox (Classic / original)', ''],

            // ---- Nintendo Switch ----
            ['Switch', 'Nintendo Switch (v1, 2017)', 'HAC-001'],
            ['Switch', 'Nintendo Switch (v2, 2019)', 'HAC-001(-01)'],
            ['Switch', 'Nintendo Switch Lite', 'HDH-001'],
            ['Switch', 'Nintendo Switch OLED', 'HEG-001'],
            ['Switch', 'Nintendo Switch 2', ''],

            // ---- Nintendo starší ----
            ['jine', 'Nintendo Wii', 'RVL-001'],
            ['jine', 'Nintendo Wii U', 'WUP-101'],
            ['jine', 'New Nintendo 3DS XL', ''],
            ['jine', 'Nintendo 3DS / 3DS XL', ''],
            ['jine', 'Nintendo 2DS / New 2DS XL', ''],
            ['jine', 'Nintendo DS Lite / DSi', ''],
            ['jine', 'Game Boy Advance / SP', ''],

            // ---- Ovladače ----
            ['ovladac', 'PS5 DualSense', 'CFI-ZCT1W'],
            ['ovladac', 'PS5 DualSense Edge', 'CFI-ZER1'],
            ['ovladac', 'PS4 DualShock 4 – v1', 'CUH-ZCT1'],
            ['ovladac', 'PS4 DualShock 4 – v2', 'CUH-ZCT2'],
            ['ovladac', 'PS3 DualShock 3 / Sixaxis', ''],
            ['ovladac', 'Xbox Series / One ovladač', '1914 / 1708'],
            ['ovladac', 'Xbox Elite Series 2', '1797'],
            ['ovladac', 'Xbox 360 ovladač', ''],
            ['ovladac', 'Nintendo Switch Joy-Con (L)', ''],
            ['ovladac', 'Nintendo Switch Joy-Con (R)', ''],
            ['ovladac', 'Nintendo Switch Pro Controller', 'HAC-013'],
            ['ovladac', 'Nintendo Switch Lite – ovládání', ''],

            // ---- PC a ostatní ----
            ['PC', 'Herní PC (stolní)', ''],
            ['PC', 'Notebook', ''],
            ['PC', 'Herní notebook', ''],
            ['PC', 'Monitor', ''],
            ['jine', 'Jiné zařízení', ''],
        ];

        foreach ($data as $i => [$kat, $nazev, $kod]) {
            KatalogZarizeni::updateOrCreate(
                ['nazev' => $nazev],
                ['kategorie' => $kat, 'model_kod' => $kod ?: null, 'poradi' => $i, 'aktivni' => true],
            );
        }
    }
}
