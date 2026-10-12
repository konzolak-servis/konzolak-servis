<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            $table->text('zarizeni_zavada')->nullable();
            $table->text('zarizeni_prace')->nullable();
        });
        Schema::table('zakazka_zarizeni', function (Blueprint $table) {
            $table->text('zavada')->nullable();
            $table->text('prace')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('zakazky', fn (Blueprint $t) => $t->dropColumn(['zarizeni_zavada', 'zarizeni_prace']));
        Schema::table('zakazka_zarizeni', fn (Blueprint $t) => $t->dropColumn(['zavada', 'prace']));
    }
};
