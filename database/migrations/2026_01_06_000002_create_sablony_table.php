<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Textové šablony pro rychlé vyplnění zakázky (závada / řešení / poznámka).
        Schema::create('sablony', function (Blueprint $table) {
            $table->id();
            $table->string('typ')->default('zavada');   // zavada | reseni | poznamka
            $table->string('nazev');
            $table->text('text');
            $table->unsignedInteger('poradi')->default(0);
            $table->boolean('aktivni')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sablony');
    }
};
