<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BazarNaklad extends Model
{
    protected $table = 'bazar_naklady';

    protected $guarded = [];

    protected $casts = [
        'datum' => 'date',
        'mnozstvi' => 'decimal:3',
        'cena_ks' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (BazarNaklad $n) {
            $n->datum ??= now()->toDateString();
            $n->mnozstvi = $n->mnozstvi ?: 1;
        });

        // Smazání nákladu z dílu → díl se vrátí zpět na sklad.
        static::deleting(function (BazarNaklad $n) {
            if ($n->sklad_polozka_id && $n->skladPolozka) {
                $n->skladPolozka->vraceni((float) $n->mnozstvi, [
                    'poznamka' => 'Vráceno z bazaru ' . ($n->obchod?->cislo ?? ''),
                ]);
            }
        });
    }

    public function obchod(): BelongsTo
    {
        return $this->belongsTo(Obchod::class, 'obchod_id');
    }

    public function skladPolozka(): BelongsTo
    {
        return $this->belongsTo(SkladPolozka::class, 'sklad_polozka_id');
    }

    public function getCenaCelkemAttribute(): float
    {
        return round((float) $this->mnozstvi * (float) $this->cena_ks, 2);
    }
}
