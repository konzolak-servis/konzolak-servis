<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zakazky', function (Blueprint $table) {
            $table->id();
            $table->string('cislo')->unique();             // SL-2026-0001
            $table->foreignId('zakaznik_id')->constrained('zakaznici')->restrictOnDelete();
            $table->foreignId('zarizeni_id')->nullable()->constrained('zarizeni')->nullOnDelete();

            $table->string('stav')->default('prijato');
            // prijato, diagnostika, ceka_na_dil, hotovo, vydano, nerentabilni, storno

            $table->date('datum_prijeti')->nullable();
            $table->date('datum_vyrizeni')->nullable();

            $table->text('prislusenstvi')->nullable();
            $table->text('popis_zavady')->nullable();       // od zákazníka
            $table->text('pozadovane_reseni')->nullable();  // od zákazníka
            $table->text('zjistena_zavada')->nullable();     // technik
            $table->text('navrh_reseni_prace')->nullable();  // technik
            $table->text('poznamka')->nullable();

            $table->decimal('predpokladana_cena', 10, 2)->nullable();
            $table->decimal('zaloha', 10, 2)->default(0);
            $table->unsignedSmallInteger('zaruka_mesice')->default(3);
            // součet řádků (práce + materiál), plní se aplikací
            $table->decimal('cena_celkem', 10, 2)->default(0);

            $table->timestamps();

            $table->index('stav');
            $table->index('datum_prijeti');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zakazky');
    }
};
