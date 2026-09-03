<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Přístupy, hesla a smlouvy k službám (hosting, doména, e-mail, software…).
        Schema::create('pristupy', function (Blueprint $table) {
            $table->id();
            $table->string('nazev');
            $table->string('kategorie')->default('jine');   // hosting | domena | email | eshop | ucet | software | jine
            $table->string('url')->nullable();
            $table->string('uzivatel')->nullable();
            $table->text('heslo')->nullable();              // šifrováno (cast encrypted)
            $table->text('poznamka')->nullable();
            $table->date('platnost_do')->nullable();        // konec platnosti / obnova / splatnost
            $table->unsignedSmallInteger('pripominka_dni')->default(14);
            $table->decimal('castka', 12, 2)->nullable();   // roční / měsíční poplatek (nepovinné)
            $table->string('doklad_soubor')->nullable();    // smlouva / faktura
            $table->boolean('aktivni')->default(true);
            $table->timestamps();

            $table->index(['kategorie', 'platnost_do']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pristupy');
    }
};
