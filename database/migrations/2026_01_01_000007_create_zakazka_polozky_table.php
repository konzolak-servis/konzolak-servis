<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Řádky na servisním protokolu / faktuře – práce a použitý materiál.
        Schema::create('zakazka_polozky', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zakazka_id')->constrained('zakazky')->cascadeOnDelete();
            $table->enum('typ', ['prace', 'material'])->default('prace');
            $table->foreignId('sklad_polozka_id')->nullable()->constrained('sklad_polozky')->nullOnDelete();
            $table->string('nazev');
            $table->decimal('mnozstvi', 12, 3)->default(1);
            $table->decimal('cena_ks', 12, 2)->default(0);
            $table->decimal('cena_celkem', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zakazka_polozky');
    }
};
