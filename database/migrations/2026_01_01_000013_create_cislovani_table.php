<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Čítače číselných řad: (typ, rok) -> poslední použité pořadové číslo.
        Schema::create('cislovani', function (Blueprint $table) {
            $table->id();
            $table->string('typ');        // zakazka, faktura, nabidka, nakup
            $table->unsignedSmallInteger('rok');
            $table->unsignedInteger('posledni')->default(0);
            $table->timestamps();

            $table->unique(['typ', 'rok']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cislovani');
    }
};
