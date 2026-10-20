<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sklad_polozky', function (Blueprint $table) {
            $table->boolean('spotrebni')->default(false)->after('aktivni');
        });
        Schema::table('nakupy', function (Blueprint $table) {
            $table->date('datum_naskladneni')->nullable()->after('datum');
        });
    }

    public function down(): void
    {
        Schema::table('sklad_polozky', fn (Blueprint $t) => $t->dropColumn('spotrebni'));
        Schema::table('nakupy', fn (Blueprint $t) => $t->dropColumn('datum_naskladneni'));
    }
};
