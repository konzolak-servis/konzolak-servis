<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nainstalované hry / PS Plus na zápůjčních konzolích (profil Konzolák se sdílením konzole):
 * seznam u zařízení, kopie v zápůjčce (stav při předání) a text v protokolu a pravidlech.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zapujcni_zarizeni', function (Blueprint $table) {
            $table->text('hry')->nullable()->after('prislusenstvi');
        });
        Schema::table('zapujcka_polozky', function (Blueprint $table) {
            $table->text('hry')->nullable()->after('prislusenstvi');
        });

        $stary = 'Konzole se předává s uživatelským profilem Konzolák (servisní profil), bez her a aplikací vypůjčitele. '
            . 'Vypůjčitel si přidá vlastní profil (účet PlayStation Network) a stáhne své hry a aplikace. '
            . 'Před vrácením je povinen smazat svůj profil a jím stažené hry a aplikace; profil Konzolák a nastavení konzole '
            . 'ponechá beze změny, konzole se vrací ve stejném stavu, v jakém byla převzata.';
        $novy = 'Konzole se předává s profilem Konzolák, na kterém je aktivní PlayStation Plus a nainstalované hry uvedené u zařízení. '
            . 'Vypůjčitel je může po dobu zápůjčky hrát přes svůj vlastní profil (účet PlayStation Network). Do profilu Konzolák '
            . 'se nepřihlašuje, nic na něm nenakupuje a nemění jeho nastavení; nainstalované hry Konzolák nemaže. Před vrácením '
            . 'smaže jen svůj profil a hry a aplikace, které si sám stáhl – konzole se vrací ve stejném stavu, v jakém byla převzata.';
        DB::table('zapujcni_typy')->where('protokol_poznamka', $stary)->update(['protokol_poznamka' => $novy]);

        $pravidloStare = 'Profil Konzolák na konzoli neměňte ani nemažte.';
        $pravidloNove = 'Profil Konzolák ani hry na něm nainstalované neměňte ani nemažte.';
        $p4Stare = 'Hry, předplatné a nákupy hradí vypůjčitel;';
        $p4Nove = 'Hry a předplatné, které si na svém účtu koupí, hradí vypůjčitel;';
        foreach (DB::table('firma')->get(['id', 'zapujcka_pravidla']) as $f) {
            $text = str_replace([$pravidloStare, $p4Stare], [$pravidloNove, $p4Nove], (string) $f->zapujcka_pravidla);
            if ($text !== (string) $f->zapujcka_pravidla) {
                DB::table('firma')->where('id', $f->id)->update(['zapujcka_pravidla' => $text]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('zapujcka_polozky', fn (Blueprint $table) => $table->dropColumn('hry'));
        Schema::table('zapujcni_zarizeni', fn (Blueprint $table) => $table->dropColumn('hry'));
    }
};
