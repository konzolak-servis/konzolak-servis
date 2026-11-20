<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Fotodokumentace stavu zařízení při předání a při vrácení (důkaz při sporu o poškození). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zapujcky', function (Blueprint $table) {
            $table->json('fotky')->nullable()->after('poznamka');
            $table->json('fotky_vraceni')->nullable()->after('poznamka_vraceni');
        });
    }

    public function down(): void
    {
        Schema::table('zapujcky', fn (Blueprint $table) => $table->dropColumn(['fotky', 'fotky_vraceni']));
    }
};
