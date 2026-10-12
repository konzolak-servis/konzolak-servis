<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            $table->boolean('dobirka')->default(false)->after('cena_dopravy');
        });
    }

    public function down(): void
    {
        Schema::table('zakazky', fn (Blueprint $t) => $t->dropColumn('dobirka'));
    }
};
