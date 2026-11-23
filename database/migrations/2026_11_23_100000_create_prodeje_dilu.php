<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Prodej dílů ze skladu zákazníkovi (mimo zakázku) – doklad o prodeji, výdej ze skladu, příjem do deníku. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prodeje_dilu', function (Blueprint $table) {
            $table->id();
            $table->string('cislo')->unique();                                   // PDL-2026-0001
            $table->date('datum');
            $table->foreignId('zakaznik_id')->nullable()->constrained('zakaznici')->nullOnDelete();
            $table->string('kupujici')->nullable();                              // když není v zákaznících
            $table->string('platba')->default('hotove');                         // hotove | qr_na_miste | ucet
            $table->unsignedTinyInteger('zaruka_mesice')->default(24);
            $table->decimal('celkem', 10, 2)->default(0);
            $table->text('poznamka')->nullable();
            $table->timestamps();
        });

        Schema::create('prodej_dilu_polozky', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodej_dilu_id')->constrained('prodeje_dilu')->cascadeOnDelete();
            $table->foreignId('sklad_polozka_id')->nullable()->constrained('sklad_polozky')->nullOnDelete();
            $table->string('nazev');
            $table->decimal('mnozstvi', 10, 3)->default(1);
            $table->decimal('cena_ks', 10, 2)->default(0);
            $table->decimal('naklad_ks', 10, 2)->default(0);                    // skladová cena v okamžiku prodeje
            $table->decimal('celkem', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prodej_dilu_polozky');
        Schema::dropIfExists('prodeje_dilu');
    }
};
