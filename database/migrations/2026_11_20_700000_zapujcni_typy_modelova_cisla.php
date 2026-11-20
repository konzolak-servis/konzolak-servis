<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Klíčová slova typů doplněná o modelová čísla (PS5 = CFI-1xxx / CFI-2xxx, DualSense = CFI-ZCT…, BDM-0xx),
 * aby se typ přiřadil i kusům z bazaru pojmenovaným jen modelem („White - CFI-1016B").
 * Pak se typ a chybějící ceny doplní existujícím zařízením.
 */
return new class extends Migration
{
    public function up(): void
    {
        $doplnit = [
            'PlayStation 5 Digital' => ['cfi-1', 'cfi-2'],
            'DualSense (ovladač k PS5)' => ['cfi-zct', 'bdm-0'],
        ];
        foreach ($doplnit as $nazev => $slova) {
            $t = DB::table('zapujcni_typy')->where('nazev', $nazev)->first();
            if (! $t) {
                continue;
            }
            $stavajici = collect(explode(',', (string) $t->klicova_slova))->map(fn ($s) => trim($s))->filter();
            $nova = $stavajici->merge($slova)->unique()->implode(', ');
            DB::table('zapujcni_typy')->where('id', $t->id)->update(['klicova_slova' => $nova]);
        }

        // zařízení bez typu: přiřadit typ a doplnit prázdné ceny (saving hook)
        foreach (\App\Models\ZapujcniZarizeni::whereNull('typ_id')->get() as $d) {
            $d->save();
        }
    }

    public function down(): void
    {
    }
};
