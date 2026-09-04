<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Staré hodnoty kategorií -> nové klíče z App\Support\Platformy. */
    private array $mapa = [
        // zařízení / katalog
        'PS5' => 'ps5', 'PS4' => 'ps4', 'PS3' => 'ps3', 'PS2' => 'ps2', 'PS1' => 'ps1',
        'Switch' => 'switch', 'Xbox' => 'xbox_series', 'PC' => 'pc',
        // ceník (mělo popisky)
        'Ovladač' => 'ovladac', 'Obecné' => 'jine', 'Nintendo Switch' => 'switch',
        // bazar
        'konzole' => 'jine',
        // beze změny
        'ovladac' => 'ovladac', 'jine' => 'jine',
    ];

    public function up(): void
    {
        foreach (['zarizeni', 'katalog_zarizeni', 'cenik'] as $tabulka) {
            foreach ($this->mapa as $stare => $nove) {
                DB::table($tabulka)->where('kategorie', $stare)->update(['kategorie' => $nove]);
            }
        }

        // Bazar (obchody) – vlastní menší číselník, sjednoť na platformy
        if (DB::getSchemaBuilder()->hasColumn('obchody', 'kategorie')) {
            foreach ($this->mapa as $stare => $nove) {
                DB::table('obchody')->where('kategorie', $stare)->update(['kategorie' => $nove]);
            }
        }
    }

    public function down(): void
    {
        // jednosměrná datová migrace – bez návratu
    }
};
