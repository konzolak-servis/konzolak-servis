<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zakazka_zarizeni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zakazka_id')->constrained('zakazky')->cascadeOnDelete();
            $table->foreignId('zarizeni_id')->constrained('zarizeni')->cascadeOnDelete();
            $table->unique(['zakazka_id', 'zarizeni_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zakazka_zarizeni');
    }
};
