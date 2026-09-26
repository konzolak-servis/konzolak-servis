<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nakup_polozky', function (Blueprint $table) {
            // Obecný provozní náklad (předplatné, kancelář, vybavení…) – nezaloží se
            // ani se nezmění skladová položka, jen se počítá do celkové částky nákupu.
            $table->boolean('neskladovat')->default(false)->after('sklad_polozka_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nakup_polozky', function (Blueprint $table) {
            $table->dropColumn('neskladovat');
        });
    }
};
