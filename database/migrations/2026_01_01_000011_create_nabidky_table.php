<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nabidky', function (Blueprint $table) {
            $table->id();
            $table->string('cislo')->unique();             // NAB-2026-0001
            $table->foreignId('zakaznik_id')->constrained('zakaznici')->restrictOnDelete();
            $table->date('datum')->nullable();
            $table->date('platnost_do')->nullable();
            $table->decimal('zaloha', 12, 2)->default(0);
            $table->decimal('celkem', 12, 2)->default(0);
            $table->string('stav')->default('nova');        // nova, prijata, zamitnuta, prevedena_na_fakturu
            $table->foreignId('faktura_id')->nullable()->constrained('faktury')->nullOnDelete();
            $table->text('poznamka')->nullable();
            $table->timestamps();
        });

        Schema::create('nabidka_polozky', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nabidka_id')->constrained('nabidky')->cascadeOnDelete();
            $table->string('skupina')->nullable();          // Základní deska, Procesor, ...
            $table->string('popis');
            $table->decimal('mnozstvi', 12, 3)->default(1);
            $table->decimal('cena', 12, 2)->default(0);
            $table->decimal('cena_celkem', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nabidka_polozky');
        Schema::dropIfExists('nabidky');
    }
};
