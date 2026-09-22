<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('zakazka_polozky', function (Blueprint $table) {
            // Skutečný náklad dílu – nezávislý na cena_ks (ta je účtovaná cena
            // zákazníkovi a smí se libovolně měnit, aniž by to zkreslilo zisk).
            $table->decimal('naklad_interni', 12, 2)->nullable()->after('cena_ks');
        });

        // Zpětně dopočítat u existujících řádků materiálu ze skladu – v době vzniku
        // byla cena_ks rovnou skutečný náklad (cena_ks_prumer), takže je to přesné.
        DB::table('zakazka_polozky')
            ->where('typ', 'material')
            ->whereNotNull('sklad_polozka_id')
            ->update(['naklad_interni' => DB::raw('cena_ks')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zakazka_polozky', function (Blueprint $table) {
            $table->dropColumn('naklad_interni');
        });
    }
};
