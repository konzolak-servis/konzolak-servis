<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faktury', function (Blueprint $table) {
            $table->id();
            $table->string('cislo')->unique();             // FA-2026-0001
            $table->string('variabilni_symbol')->nullable();
            $table->foreignId('zakazka_id')->nullable()->constrained('zakazky')->nullOnDelete();
            $table->foreignId('zakaznik_id')->constrained('zakaznici')->restrictOnDelete();
            $table->date('datum_vystaveni')->nullable();
            $table->date('datum_splatnosti')->nullable();
            $table->string('forma_uhrady')->default('převodem');
            $table->decimal('celkem', 12, 2)->default(0);
            $table->boolean('uhrazeno')->default(false);
            $table->date('datum_uhrady')->nullable();
            $table->text('poznamka')->nullable();
            $table->timestamps();

            $table->index('uhrazeno');
        });

        Schema::create('faktura_polozky', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faktura_id')->constrained('faktury')->cascadeOnDelete();
            $table->string('zarizeni_text')->nullable();
            $table->string('popis');
            $table->decimal('mnozstvi', 12, 3)->default(1);
            $table->decimal('cena', 12, 2)->default(0);
            $table->decimal('cena_celkem', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faktura_polozky');
        Schema::dropIfExists('faktury');
    }
};
