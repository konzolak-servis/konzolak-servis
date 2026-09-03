<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sklad_polozky', function (Blueprint $table) {
            $table->id();
            $table->string('nazev');
            $table->string('kod')->nullable();
            $table->string('kategorie')->nullable();
            $table->decimal('mnozstvi_skladem', 12, 3)->default(0);
            $table->decimal('min_mnozstvi', 12, 3)->default(0);
            // vážený průměr nákupní ceny za kus, přepočítává se při každém příjmu
            $table->decimal('cena_ks_prumer', 12, 2)->default(0);
            $table->string('umisteni')->nullable();
            $table->text('poznamka')->nullable();
            $table->boolean('aktivni')->default(true);
            $table->timestamps();

            $table->index('nazev');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sklad_polozky');
    }
};
