<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Datum narození vypůjčitele (identifikace k vymáhání), vedle čísla dokladu totožnosti. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zapujcky', function (Blueprint $table) {
            $table->date('datum_narozeni')->nullable()->after('doklad_totoznosti');
        });
    }

    public function down(): void
    {
        Schema::table('zapujcky', fn (Blueprint $table) => $table->dropColumn('datum_narozeni'));
    }
};
