<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakazka_polozky', function (Blueprint $table) {
            // Účtovat zákazníkovi? Práce ano, materiál ze skladu standardně ne
            // (cena dílu bývá v ceně opravy; materiál se eviduje jen kvůli skladu).
            $table->boolean('uctovat')->default(true)->after('typ');
        });

        // stávající materiálové řádky přepnout na neúčtované
        \DB::table('zakazka_polozky')->where('typ', 'material')->update(['uctovat' => false]);
    }

    public function down(): void
    {
        Schema::table('zakazka_polozky', function (Blueprint $table) {
            $table->dropColumn('uctovat');
        });
    }
};
