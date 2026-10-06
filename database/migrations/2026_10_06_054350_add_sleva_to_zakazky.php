<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            $table->string('sleva_kod')->nullable()->after('cena_dopravy');
            $table->unsignedTinyInteger('sleva_procent')->default(0)->after('sleva_kod');
        });
    }

    public function down(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            $table->dropColumn(['sleva_kod', 'sleva_procent']);
        });
    }
};
