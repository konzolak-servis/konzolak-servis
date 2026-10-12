<?php

use App\Models\KatalogZarizeni;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $poradi = (int) KatalogZarizeni::max('poradi');

        foreach ([
            ['PS5 DualSense', 'CFI-ZCT1W'],
            ['PS4 DualShock 4', 'CUH-ZCT2'],
            ['PS2 DualShock 2', 'SCPH-10010'],
            ['PS1 DualShock', 'SCPH-1200'],
            ['PS1 Analog / Dual Analog', 'SCPH-1180'],
        ] as [$nazev, $kod]) {
            if (KatalogZarizeni::where('nazev', $nazev)->exists()) {
                continue;
            }
            KatalogZarizeni::create([
                'kategorie' => 'ovladac',
                'nazev' => $nazev,
                'model_kod' => $kod,
                'poradi' => ++$poradi,
                'aktivni' => true,
            ]);
        }
    }

    public function down(): void
    {
        KatalogZarizeni::whereIn('nazev', ['PS4 DualShock 4', 'PS2 DualShock 2', 'PS1 DualShock', 'PS1 Analog / Dual Analog'])->delete();
    }
};
