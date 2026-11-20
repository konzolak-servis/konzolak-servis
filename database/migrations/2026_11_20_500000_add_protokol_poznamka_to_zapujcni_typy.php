<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Poznámka typu zařízení, která se tiskne do předávacího protokolu (např. PS5 v továrním nastavení). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zapujcni_typy', function (Blueprint $table) {
            $table->text('protokol_poznamka')->nullable()->after('prislusenstvi');
        });

        DB::table('zapujcni_typy')->where('nazev', 'PlayStation 5 Digital')->whereNull('protokol_poznamka')->update([
            'protokol_poznamka' => 'Konzole se předává ve výchozím (továrním) nastavení – prázdná, bez uživatelských účtů, her a aplikací. '
                . 'Vypůjčitel si přihlásí vlastní účet PlayStation Network a stáhne své hry a aplikace. '
                . 'Před vrácením je povinen svůj profil, hry a aplikace smazat a uvést konzoli do původního stavu, v jakém ji převzal '
                . '(Nastavení → Systém → Systémový software → Možnosti resetování → Resetovat konzoli).',
        ]);
    }

    public function down(): void
    {
        Schema::table('zapujcni_typy', fn (Blueprint $table) => $table->dropColumn('protokol_poznamka'));
    }
};
