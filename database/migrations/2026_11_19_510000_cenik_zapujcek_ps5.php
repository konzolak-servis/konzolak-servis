<?php

use App\Models\ZapujcniZarizeni;
use Illuminate\Database\Migrations\Migration;

/**
 * Ceník zápůjček:
 *  - PlayStation 5 Digital: 400 Kč/den (Po–Ne), celý víkend (Pá–Ne) 1 000 Kč
 *  - DualSense (ovladač k PS5): 50 Kč/den (Po–Ne), celý víkend (Pá–Ne) 120 Kč
 * Existující zařízení (podle názvu) se aktualizují, chybějící se založí.
 */
return new class extends Migration
{
    public function up(): void
    {
        $vse = ZapujcniZarizeni::all();
        $jeOvladac = fn ($d) => (bool) preg_match('/dual\s*sense|ovlada/iu', $d->nazev);
        $jePs5 = fn ($d) => ! $jeOvladac($d) && (bool) preg_match('/(ps|playstation)\s*5/iu', $d->nazev);

        $ps5 = ['denni_sazba' => 400, 'vikend_sazba' => null, 'vikend_cely' => 1000];
        $ovladac = ['denni_sazba' => 50, 'vikend_sazba' => null, 'vikend_cely' => 120];

        $konzole = $vse->filter($jePs5);
        $konzole->isEmpty()
            ? ZapujcniZarizeni::create(['nazev' => 'PlayStation 5 Digital', 'aktivni' => true] + $ps5)
            : $konzole->each(fn ($d) => $d->update($ps5));

        $ovladace = $vse->filter($jeOvladac);
        $ovladace->isEmpty()
            ? ZapujcniZarizeni::create(['nazev' => 'DualSense (ovladač k PS5)', 'aktivni' => true] + $ovladac)
            : $ovladace->each(fn ($d) => $d->update($ovladac));
    }

    public function down(): void
    {
        // úprava dat – bez návratu
    }
};
