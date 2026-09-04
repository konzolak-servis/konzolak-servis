<?php

namespace App\Support;

/**
 * Jednotný číselník platforem / kategorií zařízení.
 * Používá se ve skladu, ceníku, katalogu, u zařízení i v bazaru.
 */
class Platformy
{
    /** Plochá mapa hodnota => popisek. */
    public const HODNOTY = [
        'ps5' => 'PlayStation 5',
        'ps4' => 'PlayStation 4',
        'ps3' => 'PlayStation 3',
        'ps2' => 'PlayStation 2',
        'ps1' => 'PlayStation 1',
        'xbox_series' => 'Xbox Series X/S',
        'xbox_one' => 'Xbox One',
        'xbox_360' => 'Xbox 360',
        'switch' => 'Nintendo Switch',
        'wii' => 'Nintendo Wii / Wii U',
        '3ds' => 'Nintendo 3DS / DS',
        'pc' => 'PC',
        'notebook' => 'Notebook',
        'ovladac' => 'Ovladač',
        'prislusenstvi' => 'Příslušenství',
        'jine' => 'Jiné',
    ];

    /** Rozdělení do skupin (pro seskupenou roletku). */
    public const SKUPINY = [
        'PlayStation' => ['ps5', 'ps4', 'ps3', 'ps2', 'ps1'],
        'Xbox' => ['xbox_series', 'xbox_one', 'xbox_360'],
        'Nintendo' => ['switch', 'wii', '3ds'],
        'Počítače' => ['pc', 'notebook'],
        'Ostatní' => ['ovladac', 'prislusenstvi', 'jine'],
    ];

    /** Seskupené volby pro Filament Select: ['Skupina' => ['klic' => 'Popisek']]. */
    public static function volby(): array
    {
        $out = [];
        foreach (self::SKUPINY as $skupina => $klice) {
            foreach ($klice as $k) {
                $out[$skupina][$k] = self::HODNOTY[$k];
            }
        }

        return $out;
    }

    /** Popisek pro danou hodnotu (bezpečně i pro staré / prázdné). */
    public static function label(?string $hodnota): string
    {
        return self::HODNOTY[$hodnota] ?? ($hodnota ?: '—');
    }

    /** Do jaké skupiny hodnota patří (např. „PlayStation"). */
    public static function skupina(?string $hodnota): ?string
    {
        foreach (self::SKUPINY as $skupina => $klice) {
            if (in_array($hodnota, $klice, true)) {
                return $skupina;
            }
        }

        return null;
    }
}
