<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rozpis srážek z kauce při vrácení (poškození, chybějící příslušenství, čištění, jiné)
 * a forma vyrovnání (vrácení kauce / doplatek – hotově nebo převodem).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zapujcky', function (Blueprint $table) {
            $table->json('srazky')->nullable()->after('kauce_srazka');            // [{druh, popis, castka}]
            $table->string('vyrovnani_forma')->nullable()->after('srazky');      // hotově | převodem
        });

        // dosavadní jedna srážka → rozpis
        foreach (DB::table('zapujcky')->where('kauce_srazka', '>', 0)->get(['id', 'kauce_srazka', 'srazka_duvod']) as $z) {
            DB::table('zapujcky')->where('id', $z->id)->update(['srazky' => json_encode([
                ['druh' => 'jine', 'popis' => $z->srazka_duvod, 'castka' => (float) $z->kauce_srazka],
            ], JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        Schema::table('zapujcky', fn (Blueprint $table) => $table->dropColumn(['srazky', 'vyrovnani_forma']));
    }
};
