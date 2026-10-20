<?php

namespace App\Models;

use App\Support\Cisla;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Nakup extends Model
{
    protected $table = 'nakupy';

    protected $guarded = [];

    protected $casts = [
        'datum' => 'date',
        'celkem' => 'decimal:2',
        'postovne' => 'decimal:2',
        'datum_naskladneni' => 'date',
        'naskladneno' => 'boolean',
        'preneseno_do_objednavek' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Nakup $n) {
            $n->cislo ??= Cisla::dalsi('nakup', 'NAK');
            $n->datum ??= now()->toDateString();
        });
    }

    public function polozky(): HasMany
    {
        return $this->hasMany(NakupPolozka::class, 'nakup_id');
    }

    public function pohyby(): HasMany
    {
        return $this->hasMany(SkladovyPohyb::class, 'nakup_id');
    }

    /**
     * Naskladní všechny položky (příjem + přepočet váženého průměru) a zapíše výdaj
     * do peněžního deníku. Poštovné se NEROZPOČÍTÁVÁ do skladové ceny kusů (ta zůstává
     * čistě podle částky u položky) – ale připočte se k celkovému výdaji v deníku.
     */
    public function naskladnit(): void
    {
        if ($this->naskladneno) {
            return;
        }

        DB::transaction(function () {
            $datumNask = now()->toDateString();
            // spotřební materiál (pasta, tekutý kov…) se nenaskladňuje – je to náklad firmy
            $nenaskladnuje = fn ($p) => $p->neskladovat || (bool) $p->skladPolozka?->spotrebni;
            $vseNeskladovat = $this->polozky->isNotEmpty() && $this->polozky->every($nenaskladnuje);

            foreach ($this->polozky as $p) {
                // Obecný provozní náklad (předplatné, kancelář, vybavení, spotřební materiál…) – nezakládá
                // ani nemění skladovou položku, jen se počítá do celkové částky níže.
                if ($nenaskladnuje($p)) {
                    continue;
                }

                $sklad = $p->skladPolozka ?? SkladPolozka::create([
                    'nazev' => $p->nazev,
                ]);

                // Pohyb na skladě nese datum NASKLADNĚNÍ (kdy díl dorazil); datum nákupu zůstává u nákupu a v deníku.
                $sklad->prijem((float) $p->mnozstvi_ks, (float) $p->cena_ks, [
                    'zdroj' => 'nakup',
                    'nakup_id' => $this->id,
                    'datum' => $datumNask,
                    'poznamka' => $this->dodavatel,
                ]);

                if (! $p->sklad_polozka_id) {
                    $p->update(['sklad_polozka_id' => $sklad->id]);
                }
            }

            $polozkyCelkem = (float) $this->polozky()->sum('castka_celkem');
            $castka = $polozkyCelkem + (float) $this->postovne;

            PenezniDenik::create([
                'datum' => $this->datum?->toDateString() ?? now()->toDateString(),
                'typ' => 'vydej',
                'popis' => 'Nákup ' . $this->cislo . ($this->dodavatel ? ' – ' . $this->dodavatel : '')
                    . ($this->datum && $this->datum->toDateString() !== $datumNask ? ' (naskladněno ' . now()->format('d.m.') . ')' : '')
                    . ($this->postovne > 0 ? ' (vč. poštovného ' . number_format((float) $this->postovne, 0, ',', ' ') . ' Kč)' : ''),
                'castka' => $castka,
                'kategorie' => $vseNeskladovat ? 'Náklady firmy' : 'Materiál',
                'kde' => $this->dodavatel,
                'zdroj' => 'nakup',
                'zdroj_id' => $this->id,
            ]);

            $this->update(['naskladneno' => true, 'celkem' => $castka, 'datum_naskladneni' => $datumNask]);
        });
    }
}
