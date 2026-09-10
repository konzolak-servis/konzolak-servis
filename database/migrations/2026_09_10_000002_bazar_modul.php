<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bazar jako samostatný interní modul (výkup → náklady/díly → prodej → zisk).
 * ZÁMĚRNĚ se NEnapojuje na peněžní deník ani do oficiálních účetních dat –
 * má vlastní přehled a graf. Existující deníkové zápisy „obchod" se mažou.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obchody', function (Blueprint $table) {
            $table->decimal('prodejni_cena', 12, 2)->nullable()->after('cena');
            $table->date('prodej_datum')->nullable()->after('prodejni_cena');
            $table->string('prodej_komu')->nullable()->after('prodej_datum');
            $table->boolean('prodano')->default(false)->after('prodej_komu');
        });

        Schema::create('bazar_naklady', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obchod_id')->constrained('obchody')->cascadeOnDelete();
            $table->foreignId('sklad_polozka_id')->nullable()->constrained('sklad_polozky')->nullOnDelete();
            $table->string('nazev');
            $table->decimal('mnozstvi', 10, 3)->default(1);
            $table->decimal('cena_ks', 10, 2)->default(0);
            $table->date('datum');
            $table->string('poznamka')->nullable();
            $table->timestamps();
        });

        // Staré prodeje překlopit do nového životního cyklu (typ zůstává, jen doplníme prodej).
        DB::table('obchody')->where('typ', 'prodej')->update([
            'prodano' => true,
            'prodejni_cena' => DB::raw('cena'),
            'prodej_datum' => DB::raw('datum'),
        ]);

        // Bazar ven z oficiálního účetnictví – smazat deníkové zápisy z obchodů.
        DB::table('penezni_denik')->where('zdroj', 'obchod')->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('bazar_naklady');
        Schema::table('obchody', function (Blueprint $table) {
            $table->dropColumn(['prodejni_cena', 'prodej_datum', 'prodej_komu', 'prodano']);
        });
    }
};
