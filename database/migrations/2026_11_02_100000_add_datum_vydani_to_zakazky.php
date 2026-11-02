<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Datum vydání (= zaplacení) – podle něj se zapisuje příjem do deníku.
        // Datum vyřízení (hotovo) zůstává pro záruku a doklady.
        Schema::table('zakazky', function (Blueprint $table) {
            $table->date('datum_vydani')->nullable()->after('datum_vyrizeni');
        });

        // Doplnění u vydaných zakázek: den, kdy vznikl záznam příjmu v deníku
        // (= kdy byla zakázka vydána), nejdřív však datum vyřízení.
        $zakazky = DB::table('zakazky')->whereIn('stav', ['vydano', 'nerentabilni'])->get(['id', 'datum_vyrizeni']);

        foreach ($zakazky as $z) {
            $vytvoreno = DB::table('penezni_denik')
                ->where('zdroj', 'zakazka')->where('zdroj_id', $z->id)
                ->value('created_at');

            $datum = $z->datum_vyrizeni;
            if ($vytvoreno && (! $datum || substr($vytvoreno, 0, 10) > $datum)) {
                $datum = substr($vytvoreno, 0, 10);
            }

            if ($datum) {
                DB::table('zakazky')->where('id', $z->id)->update(['datum_vydani' => $datum]);
                DB::table('penezni_denik')->where('zdroj', 'zakazka')->where('zdroj_id', $z->id)->update(['datum' => $datum]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('zakazky', fn (Blueprint $t) => $t->dropColumn('datum_vydani'));
    }
};
