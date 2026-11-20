<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Zařízení převedené na zápůjční sklad ze skladu (nákupu) – při převodu zpět se kus vrátí na sklad. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zapujcni_zarizeni', function (Blueprint $table) {
            $table->foreignId('sklad_polozka_id')->nullable()->after('obchod_id')->constrained('sklad_polozky')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('zapujcni_zarizeni', fn (Blueprint $table) => $table->dropConstrainedForeignId('sklad_polozka_id'));
    }
};
