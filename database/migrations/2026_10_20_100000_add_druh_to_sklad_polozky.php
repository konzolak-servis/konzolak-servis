<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Druh skladové položky: díl (eviduje se po kusech) / spotřební materiál / nářadí.
        // Sloupec spotrebni zůstává a znamená „bez evidence kusů" (= druh není díl).
        Schema::table('sklad_polozky', function (Blueprint $table) {
            $table->string('druh', 20)->default('dil')->after('aktivni');
        });

        DB::table('sklad_polozky')->where('spotrebni', true)->update(['druh' => 'spotrebni']);
    }

    public function down(): void
    {
        Schema::table('sklad_polozky', fn (Blueprint $t) => $t->dropColumn('druh'));
    }
};
