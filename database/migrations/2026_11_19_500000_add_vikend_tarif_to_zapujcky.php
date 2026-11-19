<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Víkendový tarif: denní sazba platí Po–Čt, Pá–Ne má vlastní denní sazbu a celý víkend (Pá+So+Ne) paušál.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['zapujcni_zarizeni', 'zapujcky'] as $tabulka) {
            Schema::table($tabulka, function (Blueprint $table) {
                $table->decimal('vikend_sazba', 10, 2)->nullable()->after('denni_sazba');   // Pá–Ne Kč / den
                $table->decimal('vikend_cely', 10, 2)->nullable()->after('vikend_sazba');  // Pá+So+Ne paušál
            });
        }
    }

    public function down(): void
    {
        foreach (['zapujcni_zarizeni', 'zapujcky'] as $tabulka) {
            Schema::table($tabulka, fn (Blueprint $table) => $table->dropColumn(['vikend_sazba', 'vikend_cely']));
        }
    }
};
