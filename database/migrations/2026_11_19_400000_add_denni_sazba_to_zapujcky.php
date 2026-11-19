<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zapujcni_zarizeni', function (Blueprint $table) {
            $table->decimal('denni_sazba', 10, 2)->nullable()->after('kauce');
        });

        Schema::table('zapujcky', function (Blueprint $table) {
            $table->boolean('zdarma')->default(false)->after('kauce_forma');      // zdarma po dobu opravy
            $table->decimal('denni_sazba', 10, 2)->default(0)->after('zdarma');  // Kč / den za všechna zařízení
            $table->unsignedInteger('pocet_dni')->nullable()->after('denni_sazba');
            $table->decimal('poplatek', 10, 2)->default(0)->after('pocet_dni');   // vyúčtováno při vrácení
        });
    }

    public function down(): void
    {
        Schema::table('zapujcky', function (Blueprint $table) {
            $table->dropColumn(['zdarma', 'denni_sazba', 'pocet_dni', 'poplatek']);
        });
        Schema::table('zapujcni_zarizeni', function (Blueprint $table) {
            $table->dropColumn('denni_sazba');
        });
    }
};
