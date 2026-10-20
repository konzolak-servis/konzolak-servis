<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            $table->boolean('sleva_jen_prace')->default(false)->after('sleva_procent');
        });
    }

    public function down(): void
    {
        Schema::table('zakazky', fn (Blueprint $t) => $t->dropColumn('sleva_jen_prace'));
    }
};
