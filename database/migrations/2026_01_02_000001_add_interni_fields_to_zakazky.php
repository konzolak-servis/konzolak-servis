<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            // Interní – netiskne se na doklady, vidí jen obsluha.
            $table->text('interni_hotovo')->nullable()->after('poznamka');       // co je uděláno
            $table->text('interni_potreba')->nullable()->after('interni_hotovo'); // co je potřeba udělat
            $table->boolean('dil_objednany')->default(false)->after('interni_potreba');
            $table->string('dil_info')->nullable()->after('dil_objednany');       // jaký díl / kde / kdy
        });
    }

    public function down(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            $table->dropColumn(['interni_hotovo', 'interni_potreba', 'dil_objednany', 'dil_info']);
        });
    }
};
