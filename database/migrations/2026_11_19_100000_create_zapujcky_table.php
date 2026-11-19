<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zapujcky', function (Blueprint $table) {
            $table->id();
            $table->string('cislo')->unique();                       // ZAP-2026-0001
            $table->foreignId('zakaznik_id')->constrained('zakaznici');
            $table->foreignId('zakazka_id')->nullable()->constrained('zakazky')->nullOnDelete();
            $table->string('stav')->default('zapujceno');            // zapujceno | vraceno | storno
            $table->date('datum_zapujceni');
            $table->date('vratit_do')->nullable();
            $table->date('datum_vraceni')->nullable();
            $table->decimal('kauce', 10, 2)->default(0);
            $table->string('kauce_forma')->default('hotově');        // hotově | převodem
            $table->decimal('kauce_srazka', 10, 2)->default(0);       // část kauce, která se nevrací (poškození…)
            $table->string('srazka_duvod')->nullable();
            $table->text('poznamka')->nullable();
            $table->text('poznamka_vraceni')->nullable();
            $table->timestamps();
        });

        Schema::create('zapujcka_polozky', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zapujcka_id')->constrained('zapujcky')->cascadeOnDelete();
            $table->string('nazev');
            $table->string('seriove_cislo')->nullable();
            $table->string('prislusenstvi')->nullable();
            $table->string('stav_pri_zapujceni')->nullable();
            $table->decimal('hodnota', 10, 2)->nullable();            // hodnota zařízení (pro případ ztráty/poškození)
            $table->string('stav_pri_vraceni')->nullable();
            $table->unsignedInteger('poradi')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zapujcka_polozky');
        Schema::dropIfExists('zapujcky');
    }
};
