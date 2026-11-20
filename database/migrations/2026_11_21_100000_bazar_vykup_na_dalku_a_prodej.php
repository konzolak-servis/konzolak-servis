<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bazar:
 *  - výkup osobně (adresa, datum narození, OP, podpis) nebo na dálku (Bazoš/Facebook – inzerát, účet, důkazy místo podpisu)
 *  - prodej: lhůta odpovědnosti za vady u použitého zboží (12 / 24 měs.) a prodej na dálku (poučení o odstoupení 14 dní)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obchody', function (Blueprint $table) {
            $table->string('vykup_zpusob')->default('osobne')->after('typ');          // osobne | na_dalku
            $table->string('protistrana_adresa')->nullable()->after('protistrana_kontakt');
            $table->date('protistrana_narozeni')->nullable()->after('protistrana_adresa');
            $table->string('protistrana_ucet')->nullable()->after('protistrana_doklad');
            $table->string('inzerat_platforma')->nullable()->after('protistrana_ucet');
            $table->string('inzerat_url', 500)->nullable()->after('inzerat_platforma');
            $table->json('dukazy')->nullable()->after('inzerat_url');                  // screenshoty, potvrzení platby

            $table->boolean('prodej_na_dalku')->default(false)->after('prodej_komu');
            $table->unsignedTinyInteger('prodej_zaruka_mesice')->default(12)->after('prodej_na_dalku');
            $table->string('prodej_kontakt')->nullable()->after('prodej_komu');
        });
    }

    public function down(): void
    {
        Schema::table('obchody', fn (Blueprint $table) => $table->dropColumn([
            'vykup_zpusob', 'protistrana_adresa', 'protistrana_narozeni', 'protistrana_ucet', 'inzerat_platforma',
            'inzerat_url', 'dukazy', 'prodej_na_dalku', 'prodej_zaruka_mesice', 'prodej_kontakt',
        ]));
    }
};
