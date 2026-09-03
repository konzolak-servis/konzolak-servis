<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zakaznici', function (Blueprint $table) {
            $table->id();
            $table->enum('typ', ['osoba', 'firma'])->default('osoba');
            $table->string('jmeno')->nullable();          // u osoby: Příjmení Jméno
            $table->string('firma_nazev')->nullable();
            $table->string('ico')->nullable();
            $table->string('dic')->nullable();
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->string('ulice')->nullable();
            $table->string('mesto')->nullable();
            $table->string('psc')->nullable();
            $table->text('poznamka')->nullable();
            $table->timestamps();

            $table->index('jmeno');
            $table->index('telefon');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zakaznici');
    }
};
