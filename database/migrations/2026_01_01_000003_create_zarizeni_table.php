<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zarizeni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zakaznik_id')->constrained('zakaznici')->cascadeOnDelete();
            $table->string('kategorie')->nullable();      // PS4, PS5, Switch, Xbox, PC, ovladač, jiné
            $table->string('oznaceni');                    // "PS4 Slim CUH-2116A"
            $table->string('seriove_cislo')->nullable();
            $table->text('poznamka')->nullable();
            $table->timestamps();

            $table->index('seriove_cislo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zarizeni');
    }
};
