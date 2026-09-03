<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkladPolozka extends Model
{
    protected $table = 'sklad_polozky';

    protected $guarded = [];

    protected $casts = [
        'mnozstvi_skladem' => 'decimal:3',
        'min_mnozstvi' => 'decimal:3',
        'cena_ks_prumer' => 'decimal:2',
        'aktivni' => 'boolean',
    ];

    public function pohyby(): HasMany
    {
        return $this->hasMany(SkladovyPohyb::class, 'sklad_polozka_id');
    }

    public function getPodMinimemAttribute(): bool
    {
        return $this->min_mnozstvi > 0 && $this->mnozstvi_skladem <= $this->min_mnozstvi;
    }

    /**
     * Příjem na sklad – přepočítá vážený průměr ceny za kus a zvýší množství.
     * Vytvoří skladový pohyb typu "prijem".
     */
    public function prijem(float $mnozstvi, float $cenaZaKs, array $meta = []): SkladovyPohyb
    {
        $stareMnozstvi = (float) $this->mnozstvi_skladem;
        $stareCena = (float) $this->cena_ks_prumer;
        $noveMnozstvi = $stareMnozstvi + $mnozstvi;

        $novaCena = $noveMnozstvi > 0
            ? (($stareMnozstvi * $stareCena) + ($mnozstvi * $cenaZaKs)) / $noveMnozstvi
            : $cenaZaKs;

        $this->update([
            'mnozstvi_skladem' => $noveMnozstvi,
            'cena_ks_prumer' => round($novaCena, 2),
        ]);

        return $this->pohyby()->create(array_merge([
            'typ' => 'prijem',
            'mnozstvi' => $mnozstvi,
            'cena_ks' => round($cenaZaKs, 2),
            'datum' => now()->toDateString(),
        ], $meta));
    }

    /**
     * Výdej ze skladu (např. materiál na zakázku) v aktuální průměrné ceně.
     */
    public function vydej(float $mnozstvi, array $meta = []): SkladovyPohyb
    {
        $this->update([
            'mnozstvi_skladem' => (float) $this->mnozstvi_skladem - $mnozstvi,
        ]);

        return $this->pohyby()->create(array_merge([
            'typ' => 'vydej',
            'mnozstvi' => $mnozstvi,
            'cena_ks' => (float) $this->cena_ks_prumer,
            'datum' => now()->toDateString(),
        ], $meta));
    }

    /**
     * Vrácení dříve vydaného materiálu zpět na sklad (např. smazání řádku zakázky).
     * Nemění vážený průměr – jen navýší množství a zaloguje korekci.
     */
    public function vraceni(float $mnozstvi, array $meta = []): SkladovyPohyb
    {
        $this->update([
            'mnozstvi_skladem' => (float) $this->mnozstvi_skladem + $mnozstvi,
        ]);

        return $this->pohyby()->create(array_merge([
            'typ' => 'korekce',
            'mnozstvi' => $mnozstvi,
            'cena_ks' => (float) $this->cena_ks_prumer,
            'datum' => now()->toDateString(),
            'poznamka' => 'Vrácení materiálu na sklad',
        ], $meta));
    }
}
