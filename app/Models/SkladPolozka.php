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
        'spotrebni' => 'boolean',
    ];

    /** Druhy položek: jen díly se evidují po kusech a nabízejí v zakázce. */
    public const DRUHY = [
        'dil' => 'Díl',
        'spotrebni' => 'Spotřební materiál',
        'naradi' => 'Nářadí',
    ];

    protected static function booted(): void
    {
        // spotrebni = „bez evidence kusů" – odvozeno z druhu (používají ho nákupy i zakázky).
        static::saving(function (SkladPolozka $s) {
            $s->druh = array_key_exists((string) $s->druh, self::DRUHY) ? $s->druh : 'dil';
            $s->spotrebni = $s->druh !== 'dil';
        });

        // Položka založená rovnou s množstvím = počáteční stav (aby historie pohybů vždy sedla se stavem).
        static::created(function (SkladPolozka $s) {
            if (! $s->spotrebni && (float) $s->mnozstvi_skladem > 0 && ! $s->pohyby()->exists()) {
                $s->pohyby()->create([
                    'typ' => 'prijem',
                    'mnozstvi' => (float) $s->mnozstvi_skladem,
                    'cena_ks' => (float) $s->cena_ks_prumer,
                    'datum' => now()->toDateString(),
                    'zdroj' => 'pocatecni',
                    'poznamka' => 'Počáteční stav',
                ]);
            }
        });
    }

    public function pohyby(): HasMany
    {
        return $this->hasMany(SkladovyPohyb::class, 'sklad_polozka_id');
    }

    /**
     * Inventura – nastaví skutečný stav a zaznamená rozdíl jako pohyb (korekce, se znaménkem),
     * takže historie pohybů vždy sedí se stavem na skladě.
     */
    public function inventura(float $novyStav, ?string $poznamka = null): ?SkladovyPohyb
    {
        $rozdil = round($novyStav - (float) $this->mnozstvi_skladem, 3);
        if (abs($rozdil) < 0.0005) {
            return null;
        }

        $this->update(['mnozstvi_skladem' => $novyStav]);

        return $this->pohyby()->create([
            'typ' => 'korekce',
            'mnozstvi' => $rozdil,
            'cena_ks' => (float) $this->cena_ks_prumer,
            'datum' => now()->toDateString(),
            'zdroj' => 'inventura',
            'poznamka' => $poznamka ?: 'Inventura',
        ]);
    }

    public function getPodMinimemAttribute(): bool
    {
        return $this->min_mnozstvi > 0 && $this->mnozstvi_skladem <= $this->min_mnozstvi;
    }

    public function getPlatformaNazevAttribute(): string
    {
        return \App\Support\Platformy::label($this->platforma);
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
