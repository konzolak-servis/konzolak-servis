<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Volitelné napojení položky nákupu na zakázku (pro jakou opravu byl díl koupen). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nakup_polozky', function (Blueprint $table) {
            $table->foreignId('zakazka_id')->nullable()->after('sklad_polozka_id')
                ->constrained('zakazky')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nakup_polozky', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zakazka_id');
        });
    }
};
