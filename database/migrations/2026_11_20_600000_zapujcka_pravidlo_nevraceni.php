<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pravidlo o nevrácení: místo „kauce propadá" (u spotřebitele riziko nepřiměřené sankce)
 * započtení kauce na skutečné nároky. Mění se jen původní znění věty.
 */
return new class extends Migration
{
    public function up(): void
    {
        $stare = 'Nevrátí-li vypůjčitel zařízení do 7 dní po termínu a nebude-li dostupný, kauce propadá a servis může vymáhat hodnotu zařízení včetně nákladů.';
        $nove = 'Nevrátí-li vypůjčitel zařízení ani do 7 dní po termínu, považuje se to za ztrátu: servis započte kauci na hodnotu zařízení, cenu zapůjčení a náklady, zbytek bude vymáhat a může věc oznámit Policii ČR.';

        foreach (DB::table('firma')->get(['id', 'zapujcka_pravidla']) as $f) {
            if (str_contains((string) $f->zapujcka_pravidla, $stare)) {
                DB::table('firma')->where('id', $f->id)->update(['zapujcka_pravidla' => str_replace($stare, $nove, $f->zapujcka_pravidla)]);
            }
        }
    }

    public function down(): void
    {
    }
};
