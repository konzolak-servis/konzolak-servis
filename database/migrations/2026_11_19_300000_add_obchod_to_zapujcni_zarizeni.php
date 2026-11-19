<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zapujcni_zarizeni', function (Blueprint $table) {
            $table->foreignId('obchod_id')->nullable()->after('id')->constrained('obchody')->nullOnDelete();
        });

        // existující zařízení k zápůjčce spárovat s bazarem podle sériového čísla
        foreach (\App\Models\ZapujcniZarizeni::whereNull('obchod_id')->get() as $d) {
            $d->sparujSBazarem();
            $d->saveQuietly();
        }
    }

    public function down(): void
    {
        Schema::table('zapujcni_zarizeni', function (Blueprint $table) {
            $table->dropConstrainedForeignId('obchod_id');
        });
    }
};
