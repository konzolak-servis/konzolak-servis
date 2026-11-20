<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Konzole k zápůjčce mají profil „Konzolák" – vrací se se smazaným profilem a hrami zákazníka (ne do továrního nastavení).
 * Mění se jen původní (needitovaná) znění textů.
 */
return new class extends Migration
{
    public function up(): void
    {
        $stary = 'Konzole se předává ve výchozím (továrním) nastavení – prázdná, bez uživatelských účtů, her a aplikací. '
            . 'Vypůjčitel si přihlásí vlastní účet PlayStation Network a stáhne své hry a aplikace. '
            . 'Před vrácením je povinen svůj profil, hry a aplikace smazat a uvést konzoli do původního stavu, v jakém ji převzal '
            . '(Nastavení → Systém → Systémový software → Možnosti resetování → Resetovat konzoli).';
        $novy = 'Konzole se předává s uživatelským profilem Konzolák (servisní profil), bez her a aplikací vypůjčitele. '
            . 'Vypůjčitel si přidá vlastní profil (účet PlayStation Network) a stáhne své hry a aplikace. '
            . 'Před vrácením je povinen smazat svůj profil a jím stažené hry a aplikace; profil Konzolák a nastavení konzole '
            . 'ponechá beze změny, konzole se vrací ve stejném stavu, v jakém byla převzata.';
        DB::table('zapujcni_typy')->where('protokol_poznamka', $stary)->update(['protokol_poznamka' => $novy]);

        $pravidloStare = 'Před vrácením se odhlaste ze svých účtů a smažte osobní data – jinak je servis smaže. Za data a hry uložené vypůjčitelem servis neodpovídá.';
        $pravidloNove = 'Před vrácením smažte svůj profil, stažené hry a osobní data – jinak je servis smaže. Profil Konzolák na konzoli neměňte ani nemažte. Za data a hry uložené vypůjčitelem servis neodpovídá.';
        foreach (DB::table('firma')->get(['id', 'zapujcka_pravidla']) as $f) {
            if (str_contains((string) $f->zapujcka_pravidla, $pravidloStare)) {
                DB::table('firma')->where('id', $f->id)->update(['zapujcka_pravidla' => str_replace($pravidloStare, $pravidloNove, $f->zapujcka_pravidla)]);
            }
        }
    }

    public function down(): void
    {
    }
};
