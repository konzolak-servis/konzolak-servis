<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Poštovné zadané už při objednávce – předvyplní se při naskladnění (výdaj v deníku).
        Schema::table('objednavky_dilu', function (Blueprint $table) {
            $table->decimal('postovne', 10, 2)->default(0)->after('cena_odhad');
        });
    }

    public function down(): void
    {
        Schema::table('objednavky_dilu', fn (Blueprint $t) => $t->dropColumn('postovne'));
    }
};
