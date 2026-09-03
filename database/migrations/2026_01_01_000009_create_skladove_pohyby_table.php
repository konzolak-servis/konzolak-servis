<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skladove_pohyby', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sklad_polozka_id')->constrained('sklad_polozky')->cascadeOnDelete();
            $table->enum('typ', ['prijem', 'vydej', 'korekce'])->default('prijem');
            $table->decimal('mnozstvi', 12, 3);            // vždy kladné; význam dává typ
            $table->decimal('cena_ks', 12, 2)->default(0);
            $table->date('datum')->nullable();
            $table->string('zdroj')->nullable();           // nakup, zakazka, rucne
            $table->foreignId('nakup_id')->nullable()->constrained('nakupy')->nullOnDelete();
            $table->foreignId('zakazka_id')->nullable()->constrained('zakazky')->nullOnDelete();
            $table->text('poznamka')->nullable();
            $table->timestamps();

            $table->index(['sklad_polozka_id', 'datum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skladove_pohyby');
    }
};
