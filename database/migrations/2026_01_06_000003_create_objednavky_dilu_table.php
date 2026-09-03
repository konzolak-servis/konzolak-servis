<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objednavky_dilu', function (Blueprint $table) {
            $table->id();
            $table->string('cislo')->unique();                // OBJ-2026-0001
            $table->string('dodavatel')->nullable();
            $table->date('datum_objednavky')->nullable();
            $table->date('ocekavane_doruceni')->nullable();
            $table->date('doruceno_datum')->nullable();
            $table->string('nazev_dilu');
            $table->decimal('mnozstvi', 12, 3)->default(1);
            $table->decimal('cena_odhad', 12, 2)->nullable();
            $table->string('stav')->default('objednano');     // objednano | dorazilo | zruseno
            $table->foreignId('zakazka_id')->nullable()->constrained('zakazky')->nullOnDelete();
            $table->text('poznamka')->nullable();
            $table->timestamps();

            $table->index('stav');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objednavky_dilu');
    }
};
