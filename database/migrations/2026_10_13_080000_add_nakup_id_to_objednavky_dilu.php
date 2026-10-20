<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objednavky_dilu', function (Blueprint $table) {
            $table->unsignedBigInteger('nakup_id')->nullable()->after('zakazka_id');
        });
    }

    public function down(): void
    {
        Schema::table('objednavky_dilu', fn (Blueprint $t) => $t->dropColumn('nakup_id'));
    }
};
