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
        'naskladneno' => 'boolean',
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
     * Naskladní všechny položky (příjem + přepočet váženého průměru)
     * a zapíše celkovou částku jako výdaj do peněžního deníku.
     */
    public function naskladnit(): void
    {
        if ($this->naskladneno) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->polozky as $p) {
                $sklad = $p->skladPolozka ?? SkladPolozka::create([
                    'nazev' => $p->nazev,
                ]);

                $sklad->prijem((float) $p->mnozstvi_ks, (float) $p->cena_ks, [
                    'zdroj' => 'nakup',
                    'nakup_id' => $this->id,
                    'datum' => $this->datum?->toDateString() ?? now()->toDateString(),
                    'poznamka' => $this->dodavatel,
                ]);

                if (! $p->sklad_polozka_id) {
                    $p->update(['sklad_polozka_id' => $sklad->id]);
                }
            }

            $castka = $this->celkem > 0
                ? (float) $this->celkem
                : (float) $this->polozky()->sum('castka_celkem');

            PenezniDenik::create([
                'datum' => $this->datum?->toDateString() ?? now()->toDateString(),
                'typ' => 'vydej',
                'popis' => 'Nákup ' . $this->cislo . ($this->dodavatel ? ' – ' . $this->dodavatel : ''),
                'castka' => $castka,
                'kategorie' => 'Materiál',
                'kde' => $this->dodavatel,
                'zdroj' => 'nakup',
                'zdroj_id' => $this->id,
            ]);

            $this->update(['naskladneno' => true, 'celkem' => $castka]);
        });
    }
}
