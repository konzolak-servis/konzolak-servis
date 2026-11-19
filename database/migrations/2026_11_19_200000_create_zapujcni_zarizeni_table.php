<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zapujcni_zarizeni', function (Blueprint $table) {
            $table->id();
            $table->string('nazev');
            $table->string('seriove_cislo')->nullable();
            $table->string('prislusenstvi')->nullable();
            $table->string('stav_popis')->nullable();            // aktuální technický stav (funkční, odřený roh…)
            $table->decimal('hodnota', 10, 2)->nullable();
            $table->decimal('kauce', 10, 2)->nullable();          // doporučená kauce
            $table->boolean('aktivni')->default(true);            // vyřazené se nenabízí
            $table->text('poznamka')->nullable();
            $table->timestamps();
        });

        Schema::table('zapujcka_polozky', function (Blueprint $table) {
            $table->foreignId('zapujcni_zarizeni_id')->nullable()->after('zapujcka_id')
                ->constrained('zapujcni_zarizeni')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('zapujcka_polozky', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zapujcni_zarizeni_id');
        });
        Schema::dropIfExists('zapujcni_zarizeni');
    }
};
