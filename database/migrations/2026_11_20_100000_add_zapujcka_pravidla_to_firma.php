<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pravidla zápůjčky na předávacím protokolu – upravitelná v Nastavení firmy (každý řádek = jedno pravidlo).
 */
return new class extends Migration
{
    public const VYCHOZI = <<<'TXT'
Zařízení je majetkem servisu. Vypůjčitel ho nesmí prodat, zastavit, půjčit ani jinak přenechat třetí osobě.
Zařízení se nesmí otevírat, opravovat ani upravovat (firmware, jailbreak, modifikace); nesmí se odstraňovat štítky a plomby.
Zařízení používejte jen v domácnosti a chraňte ho před vodou, pády, přehřátím (nezakrývat větrací otvory), kouřem a domácími zvířaty.
Na zařízení se přihlašujte jen vlastním účtem (PSN, Xbox, Nintendo…). Hry, předplatné a nákupy provedené na zařízení hradí vypůjčitel.
Před vrácením se odhlaste ze svých účtů a smažte osobní data – jinak je servis smaže. Za data a hry uložené vypůjčitelem servis neodpovídá.
Závadu nebo poškození hlaste ihned na tel. {telefon}; neodstraňujte ji sami.
Zařízení vraťte v dohodnutý den, kompletní včetně příslušenství a čisté. Za každý den prodlení se účtuje denní sazba dle ceníku.
U zápůjčky zdarma po dobu opravy je nutné zařízení vrátit nejpozději při vyzvednutí opraveného zařízení; za každý další den se účtuje denní sazba.
Kauce se vrací po kontrole zařízení. Strhnout z ní lze cenu zapůjčení, poplatek za prodlení, chybějící příslušenství, čištění a opravu poškození.
Při ztrátě, zničení nebo neopravitelném poškození uhradí vypůjčitel hodnotu zařízení uvedenou v tomto protokolu.
Nevrátí-li vypůjčitel zařízení do 7 dní po termínu a nebude-li dostupný, kauce propadá a servis může vymáhat hodnotu zařízení včetně nákladů.
Vypůjčitel si zařízení při převzetí prohlédl a vyzkoušel; s uvedeným stavem a příslušenstvím souhlasí.
TXT;

    public function up(): void
    {
        Schema::table('firma', function (Blueprint $table) {
            $table->text('zapujcka_pravidla')->nullable();
        });

        DB::table('firma')->whereNull('zapujcka_pravidla')->update(['zapujcka_pravidla' => self::VYCHOZI]);
    }

    public function down(): void
    {
        Schema::table('firma', fn (Blueprint $table) => $table->dropColumn('zapujcka_pravidla'));
    }
};
