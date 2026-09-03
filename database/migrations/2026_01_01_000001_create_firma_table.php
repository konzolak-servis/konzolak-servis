<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nastavení firmy – jediný řádek, používá se v hlavičkách dokladů.
        Schema::create('firma', function (Blueprint $table) {
            $table->id();
            $table->string('nazev')->default('Konzolák Zlín');
            $table->string('ico')->nullable();
            $table->string('dic')->nullable();
            $table->string('ulice')->nullable();
            $table->string('mesto')->nullable();
            $table->string('psc')->nullable();
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->string('web')->nullable();
            $table->string('cislo_uctu')->nullable();
            $table->boolean('platce_dph')->default(false);
            $table->unsignedSmallInteger('splatnost_dni')->default(14);
            $table->unsignedSmallInteger('zaruka_mesice')->default(3);
            $table->text('pravni_text_servisni_list')->nullable();
            $table->text('pravni_text_protokol')->nullable();
            $table->text('pravni_text_faktura')->nullable();
            $table->text('pravni_text_nabidka')->nullable();
            $table->string('logo_cesta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firma');
    }
};
