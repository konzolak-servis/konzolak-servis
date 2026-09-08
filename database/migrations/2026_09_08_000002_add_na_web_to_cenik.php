<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Příznak, zda se položka ceníku zobrazuje na veřejném webu. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cenik', function (Blueprint $table) {
            $table->boolean('na_web')->default(true)->after('aktivni');
        });
    }

    public function down(): void
    {
        Schema::table('cenik', function (Blueprint $table) {
            $table->dropColumn('na_web');
        });
    }
};
