<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Objednávky dílů mají nově jen jednu cenu „vč. poštovného". U dosud nenaskladněných
 * (bez nákupu) se zadané poštovné přičte k ceně a vynuluje.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('objednavky_dilu')
            ->whereNull('nakup_id')
            ->where('postovne', '>', 0)
            ->update([
                'cena_odhad' => DB::raw('COALESCE(cena_odhad, 0) + postovne'),
                'postovne' => 0,
            ]);
    }

    public function down(): void
    {
        // jednosměrná úprava dat
    }
};
