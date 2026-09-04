<?php

use App\Models\KatalogZarizeni;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Po sjednocení číselníku spadly Xbox One / Xbox 360 modely pod „xbox_series".
     * Odvodíme platformu znovu z názvu modelu (názvy v katalogu jsou čisté).
     */
    public function up(): void
    {
        foreach (KatalogZarizeni::all() as $m) {
            $spravna = KatalogZarizeni::kategorieZNazvu($m->nazev);

            if ($spravna !== 'jine' && $spravna !== $m->kategorie) {
                $m->update(['kategorie' => $spravna]);
            }
        }
    }

    public function down(): void
    {
        // jednosměrná datová oprava
    }
};
