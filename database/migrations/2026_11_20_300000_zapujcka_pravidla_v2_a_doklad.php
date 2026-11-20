<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pravidla zápůjčky v2 (doplněno: závada bez zavinění, výpadky PSN, místo vrácení, totožnost)
 * + číslo dokladu totožnosti vypůjčitele na zápůjčce.
 * Pravidla se přepíšou jen tam, kde je text stále výchozí (ručně upravený se nemění).
 */
return new class extends Migration
{
    public const NOVA = <<<'TXT'
Zařízení je majetkem servisu. Vypůjčitel ho nesmí prodat, zastavit ani bez souhlasu servisu přenechat třetí osobě.
Zařízení se nesmí otevírat, opravovat ani upravovat (firmware, jailbreak, modifikace); nesmí se odstraňovat štítky a plomby.
Zařízení používejte jen v domácnosti a chraňte ho před vodou, pády, přehřátím (nezakrývat větrací otvory), prachem, kouřem a domácími zvířaty.
Na zařízení se přihlašujte jen vlastním účtem (PSN, Xbox, Nintendo…). Hry, předplatné a nákupy hradí vypůjčitel; za výpadky internetu a online služeb (PSN apod.) servis neodpovídá.
Před vrácením se odhlaste ze svých účtů a smažte osobní data – jinak je servis smaže. Za data a hry uložené vypůjčitelem servis neodpovídá.
Při závadě zařízení přestaňte používat a ihned ji hlaste na tel. {telefon}; neodstraňujte ji sami. Vznikne-li závada bez zavinění vypůjčitele, servis poskytne náhradní zařízení nebo vrátí poměrnou část ceny.
Zařízení vraťte v dohodnutý den v provozovně servisu, kompletní včetně příslušenství a přiměřeně čisté. Za každý den prodlení se účtuje denní sazba dle ceníku.
U zápůjčky zdarma po dobu opravy je nutné zařízení vrátit nejpozději při vyzvednutí opraveného zařízení; za každý další den se účtuje denní sazba.
Kauce se vrací po kontrole zařízení. Strhnout z ní lze cenu zapůjčení, poplatek za prodlení, chybějící příslušenství, čištění a opravu poškození.
Při ztrátě, zničení nebo neopravitelném poškození uhradí vypůjčitel hodnotu zařízení uvedenou v tomto protokolu.
Nevrátí-li vypůjčitel zařízení do 7 dní po termínu a nebude-li dostupný, kauce propadá a servis může vymáhat hodnotu zařízení včetně nákladů.
Vypůjčitel při převzetí prokázal totožnost, zařízení si prohlédl a vyzkoušel; s uvedeným stavem a příslušenstvím souhlasí.
TXT;

    public function up(): void
    {
        Schema::table('zapujcky', function (Blueprint $table) {
            $table->string('doklad_totoznosti')->nullable()->after('zakazka_id');
        });

        $stara = (require __DIR__ . '/2026_11_20_100000_add_zapujcka_pravidla_to_firma.php')::VYCHOZI;
        $norm = fn ($t) => preg_replace('/\R/', "\n", trim((string) $t));
        foreach (DB::table('firma')->get(['id', 'zapujcka_pravidla']) as $f) {
            if ($f->zapujcka_pravidla === null || $norm($f->zapujcka_pravidla) === $norm($stara)) {
                DB::table('firma')->where('id', $f->id)->update(['zapujcka_pravidla' => self::NOVA]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('zapujcky', fn (Blueprint $table) => $table->dropColumn('doklad_totoznosti'));
    }
};
