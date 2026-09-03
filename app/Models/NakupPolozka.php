<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NakupPolozka extends Model
{
    protected $table = 'nakup_polozky';

    protected $guarded = [];

    protected $casts = [
        'mnozstvi_ks' => 'decimal:3',
        'castka_celkem' => 'decimal:2',
        'cena_ks' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Zadává se počet ks + celková částka; cena za kus se dopočítá.
        static::saving(function (NakupPolozka $p) {
            $p->cena_ks = (float) $p->mnozstvi_ks > 0
                ? round((float) $p->castka_celkem / (float) $p->mnozstvi_ks, 2)
                : 0;
        });
    }

    public function nakup(): BelongsTo
    {
        return $this->belongsTo(Nakup::class, 'nakup_id');
    }

    public function skladPolozka(): BelongsTo
    {
        return $this->belongsTo(SkladPolozka::class, 'sklad_polozka_id');
    }
}
