<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Výkup / prodej použitých ovladačů, konzolí a PC (bazar).
        Schema::create('obchody', function (Blueprint $table) {
            $table->id();
            $table->string('cislo')->unique();               // VYK-2026-0001 / PRD-2026-0001
            $table->enum('typ', ['vykup', 'prodej']);
            $table->date('datum');
            $table->string('kategorie')->nullable();          // ovladac / konzole / PC / jiné
            $table->string('nazev');                          // "PS5 DualSense bílý"
            $table->string('seriove_cislo')->nullable();
            $table->text('stav_popis')->nullable();           // popis stavu / příslušenství
            $table->decimal('cena', 12, 2)->default(0);

            $table->string('protistrana_jmeno')->nullable();
            $table->string('protistrana_kontakt')->nullable();
            $table->string('protistrana_doklad')->nullable(); // č. OP / dokladu totožnosti (u výkupu)

            $table->text('poznamka')->nullable();

            $table->foreignId('sklad_polozka_id')->nullable()->constrained('sklad_polozky')->nullOnDelete();
            $table->boolean('vyrizeno')->default(false);      // potvrzeno → pohyb peněz + skladu
            $table->timestamps();

            $table->index(['typ', 'datum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obchody');
    }
};
