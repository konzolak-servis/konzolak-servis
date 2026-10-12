<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakazka_zarizeni', function (Blueprint $table) {
            $table->text('pozadovane')->nullable()->after('zavada');
        });
    }

    public function down(): void
    {
        Schema::table('zakazka_zarizeni', fn (Blueprint $t) => $t->dropColumn('pozadovane'));
    }
};
