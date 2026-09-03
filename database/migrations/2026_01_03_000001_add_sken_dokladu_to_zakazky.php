<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            // Sken podepsaného dokladu (PDF/foto) k dokončené zakázce.
            $table->string('sken_dokladu')->nullable()->after('dil_info');
        });
    }

    public function down(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            $table->dropColumn('sken_dokladu');
        });
    }
};
