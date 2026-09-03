<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nakupy', function (Blueprint $table) {
            $table->id();
            $table->string('cislo')->unique();             // NAK-2026-0001
            $table->string('dodavatel')->nullable();       // Alza, Allegro, Konzoliste, Hadex
            $table->date('datum')->nullable();
            $table->decimal('celkem', 12, 2)->default(0);
            $table->boolean('naskladneno')->default(false); // po potvrzení vytvoří skladové pohyby + výdaj v deníku
            $table->text('poznamka')->nullable();
            $table->string('doklad_soubor')->nullable();
            $table->timestamps();
        });

        Schema::create('nakup_polozky', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nakup_id')->constrained('nakupy')->cascadeOnDelete();
            $table->foreignId('sklad_polozka_id')->nullable()->constrained('sklad_polozky')->nullOnDelete();
            $table->string('nazev');
            $table->decimal('mnozstvi_ks', 12, 3)->default(1);
            $table->decimal('castka_celkem', 12, 2)->default(0);      // zadává uživatel
            $table->decimal('cena_ks', 12, 2)->default(0);            // dopočítá se = castka_celkem / mnozstvi_ks
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nakup_polozky');
        Schema::dropIfExists('nakupy');
    }
};
