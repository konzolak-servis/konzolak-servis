<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Typy zápůjčních zařízení = šablony ceníku (cena za den, víkend, kauce, příslušenství…),
 * které se předvyplní každému fyzickému zařízení na zápůjčním skladu.
 * Dva dřív založené „ceníkové" záznamy (PS5 Digital, DualSense) se převedou na typy a smažou.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zapujcni_typy', function (Blueprint $table) {
            $table->id();
            $table->string('nazev');
            $table->string('klicova_slova')->nullable();           // pro automatické přiřazení podle názvu zařízení
            $table->decimal('denni_sazba', 10, 2)->nullable();
            $table->decimal('vikend_cely', 10, 2)->nullable();
            $table->decimal('kauce', 10, 2)->nullable();
            $table->decimal('hodnota', 10, 2)->nullable();
            $table->string('prislusenstvi')->nullable();
            $table->text('poznamka')->nullable();
            $table->unsignedInteger('poradi')->default(0);
            $table->timestamps();
        });

        Schema::table('zapujcni_zarizeni', function (Blueprint $table) {
            $table->foreignId('typ_id')->nullable()->after('id')->constrained('zapujcni_typy')->nullOnDelete();
        });

        $typy = [
            ['nazev' => 'PlayStation 5 Digital', 'klicova_slova' => 'ps5, playstation 5', 'denni_sazba' => 400, 'vikend_cely' => 1000,
                'prislusenstvi' => 'napájecí kabel, HDMI kabel, 1× ovladač DualSense, USB-C kabel', 'poradi' => 1],
            ['nazev' => 'DualSense (ovladač k PS5)', 'klicova_slova' => 'dualsense, ovladač ps5', 'denni_sazba' => 50, 'vikend_cely' => 120,
                'prislusenstvi' => 'USB-C kabel', 'poradi' => 2],
        ];
        foreach ($typy as $t) {
            DB::table('zapujcni_typy')->insert($t + ['created_at' => now(), 'updated_at' => now()]);
        }

        // ceníkové záznamy, které nebyly fyzickým zařízením – smazat jen když nemají žádnou vazbu ani historii
        DB::table('zapujcni_zarizeni')
            ->whereIn('nazev', ['PlayStation 5 Digital', 'DualSense (ovladač k PS5)'])
            ->whereNull('obchod_id')->whereNull('sklad_polozka_id')
            ->where(fn ($q) => $q->whereNull('seriove_cislo')->orWhere('seriove_cislo', ''))
            ->whereNotExists(fn ($q) => $q->from('zapujcka_polozky')->whereColumn('zapujcka_polozky.zapujcni_zarizeni_id', 'zapujcni_zarizeni.id'))
            ->delete();

        // existující zařízení přiřadit k typu podle názvu
        foreach (\App\Models\ZapujcniZarizeni::whereNull('typ_id')->get() as $d) {
            if ($typ = \App\Models\ZapujcniTyp::najdiPodleNazvu($d->nazev)) {
                $d->typ_id = $typ->id;
                $d->saveQuietly();
            }
        }
    }

    public function down(): void
    {
        Schema::table('zapujcni_zarizeni', fn (Blueprint $table) => $table->dropConstrainedForeignId('typ_id'));
        Schema::dropIfExists('zapujcni_typy');
    }
};
