<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            $table->string('zpusob_vydani')->default('osobne')->after('zpusob_uhrady')
                ->comment('osobne / odeslani – jak zákazník dostane hotové zařízení zpět');
            $table->date('odeslano_datum')->nullable()->after('zpusob_vydani');
            $table->string('dopravce')->nullable()->after('odeslano_datum')
                ->comment('Zásilkovna, Česká pošta, PPL…');
            $table->string('sledovaci_cislo')->nullable()->after('dopravce');
            $table->decimal('cena_dopravy', 10, 2)->default(0)->after('sledovaci_cislo')
                ->comment('Doprava/dobírka připočtená k úhradě zákazníkem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            $table->dropColumn(['zpusob_vydani', 'odeslano_datum', 'dopravce', 'sledovaci_cislo', 'cena_dopravy']);
        });
    }
};
