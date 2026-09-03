<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Číselník servisních úkonů – předvyplnění řádků práce na zakázce. Plně editovatelný.
        Schema::create('cenik', function (Blueprint $table) {
            $table->id();
            $table->string('kategorie')->nullable();       // Ovladač, PS4, PS5, Switch, Obecné
            $table->string('nazev');
            $table->decimal('cena', 10, 2)->default(0);
            $table->boolean('aktivni')->default(true);
            $table->unsignedInteger('poradi')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cenik');
    }
};
