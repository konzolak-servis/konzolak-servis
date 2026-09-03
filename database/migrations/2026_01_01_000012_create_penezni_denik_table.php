<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Peněžní deník = dnešní "Výkaz příjmů a výdajů" (jen servis).
        Schema::create('penezni_denik', function (Blueprint $table) {
            $table->id();
            $table->date('datum');
            $table->enum('typ', ['prijem', 'vydej']);
            $table->string('popis');
            $table->decimal('castka', 12, 2);
            $table->string('kategorie')->nullable();
            $table->string('kde')->nullable();              // u výdeje: kde nakoupeno
            $table->string('zdroj')->nullable();            // zakazka, faktura, nakup, rucne
            $table->unsignedBigInteger('zdroj_id')->nullable();
            $table->timestamps();

            $table->index(['datum', 'typ']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penezni_denik');
    }
};
