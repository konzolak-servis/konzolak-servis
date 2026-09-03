<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nabidka_polozky', function (Blueprint $table) {
            $table->string('varianta')->default('nova')->after('popis');   // nova | bazar
            $table->decimal('cena_nova', 12, 2)->nullable()->after('varianta');
            $table->decimal('cena_bazar', 12, 2)->nullable()->after('cena_nova');
            $table->decimal('naklad_interni', 12, 2)->nullable()->after('cena_bazar'); // skryté – jen pro mě
            $table->string('eshop_url')->nullable()->after('naklad_interni');           // odkaz na e-shop
        });
    }

    public function down(): void
    {
        Schema::table('nabidka_polozky', function (Blueprint $table) {
            $table->dropColumn(['varianta', 'cena_nova', 'cena_bazar', 'naklad_interni', 'eshop_url']);
        });
    }
};
