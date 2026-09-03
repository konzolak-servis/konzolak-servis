<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Číselník modelů zařízení – při zakázce stačí vybrat model a doplnit SN.
        Schema::create('katalog_zarizeni', function (Blueprint $table) {
            $table->id();
            $table->string('kategorie');            // PS5, PS4, Switch, Xbox, ovladac, PC, jine
            $table->string('nazev');                // "PlayStation 5 – Fat (disková)"
            $table->string('model_kod')->nullable(); // "CFI-10xx"
            $table->unsignedInteger('poradi')->default(0);
            $table->boolean('aktivni')->default(true);
            $table->timestamps();

            $table->index(['kategorie', 'poradi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('katalog_zarizeni');
    }
};
