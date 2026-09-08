<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Příznak, že položky nákupu už byly přeneseny do Objednávek dílů. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nakupy', function (Blueprint $table) {
            $table->boolean('preneseno_do_objednavek')->default(false)->after('naskladneno');
        });
    }

    public function down(): void
    {
        Schema::table('nakupy', function (Blueprint $table) {
            $table->dropColumn('preneseno_do_objednavek');
        });
    }
};
