<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Počítadlo návštěv veřejného webu: 1 řádek = 1 unikátní návštěvník za den.
        // `navstevnik` je jednosměrný otisk (IP + prohlížeč + den + app key) – IP se neukládá.
        Schema::create('navstevy_webu', function (Blueprint $table) {
            $table->id();
            $table->date('den');
            $table->char('navstevnik', 64);
            $table->unsignedInteger('zobrazeni')->default(1);
            $table->string('vstupni_stranka', 191)->nullable();
            $table->string('odkud', 191)->nullable();
            $table->timestamps();

            $table->unique(['den', 'navstevnik']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navstevy_webu');
    }
};
