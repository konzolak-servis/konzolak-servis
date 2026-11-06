<?php

use App\Models\Nakup;
use App\Models\SkladovyPohyb;
use App\Models\SkladPolozka;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Zpětně: poštovné u už naskladněných nákupů se rozpočítá do skladové ceny (stejně jako
 * nově v Nakup::naskladnit). Příjmové pohyby dostanou cenu vč. podílu poštovného a průměrná
 * cena skladových položek se posune o rozdíl mezi přepočtem historie „bez" a „s" poštovným
 * (ruční úpravy průměru tím zůstanou zachované). Množství skladem se nemění.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $navyseni = []; // pohyb_id => Kč navíc za kus

            foreach (Nakup::with(['polozky', 'pohyby'])->where('naskladneno', true)->where('postovne', '>', 0)->get() as $n) {
                $postovne = (float) $n->postovne;
                $suma = (float) $n->polozky->sum('castka_celkem');
                $pocet = max(1, $n->polozky->count());

                foreach ($n->pohyby->where('typ', 'prijem') as $pohyb) {
                    $polozky = $n->polozky->where('sklad_polozka_id', $pohyb->sklad_polozka_id);
                    $ks = (float) $polozky->sum('mnozstvi_ks');
                    if ($polozky->isEmpty() || $ks <= 0) {
                        continue;
                    }
                    $podil = $polozky->sum(fn ($p) => $postovne * ($suma > 0 ? (float) $p->castka_celkem / $suma : 1 / $pocet));
                    $navyseni[$pohyb->id] = $podil / $ks;
                }
            }

            $skladIds = SkladovyPohyb::whereIn('id', array_keys($navyseni))->pluck('sklad_polozka_id')->unique();

            foreach (SkladPolozka::whereIn('id', $skladIds)->get() as $s) {
                $stara = $s->prumerZHistorie();
                $nova = $s->prumerZHistorie([], $navyseni);

                foreach ($s->pohyby()->whereIn('id', array_keys($navyseni))->get() as $p) {
                    $p->update(['cena_ks' => round((float) $p->cena_ks + $navyseni[$p->id], 2)]);
                }

                $s->update(['cena_ks_prumer' => max(0, round((float) $s->cena_ks_prumer + ($nova - $stara), 2))]);
            }
        });
    }

    public function down(): void
    {
        // jednosměrná úprava dat
    }
};
