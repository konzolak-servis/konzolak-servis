<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EET 2.0 (zákon č. 180/2026 Sb., od 1. 1. 2027): nastavení napojení a evidence odeslaných tržeb.
 * Eviduje se jen kontaktní platba (hotově, QR/převod přímo na místě); platba na dálku ne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firma', function (Blueprint $table) {
            $table->boolean('eet_aktivni')->default(false);
            $table->string('eet_prostredi')->default('playground');   // playground | produkce
            $table->boolean('eet_overeni')->default(false);          // ověřovací režim (nic se neeviduje)
            $table->date('eet_od')->nullable();                      // evidovat příjmy od tohoto data
            $table->string('eet_dic')->nullable();                   // EIČ, např. CZ8551015704
            $table->unsignedInteger('eet_id_jednotky')->nullable();  // evidenční jednotka z DIS+
            $table->string('eet_id_pokl', 20)->default('1');         // označení pokladního zařízení
            $table->string('eet_cert_soubor')->nullable();           // .p12 (privátní úložiště)
            $table->text('eet_cert_heslo')->nullable();              // šifrované
        });

        Schema::table('penezni_denik', function (Blueprint $table) {
            $table->boolean('eet_na_miste')->default(false);         // platba převodem/QR přijatá na místě
            $table->boolean('eet_vyjmout')->default(false);          // hotovost, která se neeviduje (dobírka)
        });

        Schema::table('zakazky', function (Blueprint $table) {
            $table->boolean('platba_na_miste')->default(false);      // převod/QR zaplacen přímo v provozovně
        });

        Schema::table('obchody', function (Blueprint $table) {
            $table->string('prodej_platba')->nullable();             // hotove | qr_na_miste | ucet
        });

        Schema::create('eet_trzby', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penezni_denik_id')->nullable()->constrained('penezni_denik')->nullOnDelete();
            $table->string('popis')->nullable();
            $table->string('porad_cis', 25);
            $table->string('dat_trzby', 25);                        // přesně odeslaný řetězec (kvůli opakování)
            $table->decimal('celk_trzba', 12, 2);
            $table->boolean('storno')->default(false);
            $table->string('stav')->default('ceka');                // ceka | odeslano | chyba
            $table->string('prostredi')->nullable();
            $table->boolean('overeni')->default(false);
            $table->string('pok', 39)->nullable();
            $table->unsignedSmallInteger('pokusy')->default(0);
            $table->integer('chyba_kod')->nullable();
            $table->string('chyba_text')->nullable();
            $table->json('varovani')->nullable();
            $table->string('transakce_id', 64)->nullable();         // X-Global-Transaction-Id
            $table->timestamp('odeslano_at')->nullable();
            $table->timestamps();

            $table->index(['stav', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eet_trzby');
        Schema::table('penezni_denik', fn (Blueprint $table) => $table->dropColumn(['eet_na_miste', 'eet_vyjmout']));
        Schema::table('zakazky', fn (Blueprint $table) => $table->dropColumn('platba_na_miste'));
        Schema::table('obchody', fn (Blueprint $table) => $table->dropColumn('prodej_platba'));
        Schema::table('firma', fn (Blueprint $table) => $table->dropColumn([
            'eet_aktivni', 'eet_prostredi', 'eet_overeni', 'eet_od', 'eet_dic', 'eet_id_jednotky', 'eet_id_pokl',
            'eet_cert_soubor', 'eet_cert_heslo',
        ]));
    }
};
