<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Doklad o koupi nahraný u objednávky – při naskladnění se připojí k nákupu.
        Schema::table('objednavky_dilu', function (Blueprint $table) {
            $table->string('doklad_soubor')->nullable()->after('poznamka');
        });
    }

    public function down(): void
    {
        Schema::table('objednavky_dilu', fn (Blueprint $t) => $t->dropColumn('doklad_soubor'));
    }
};
