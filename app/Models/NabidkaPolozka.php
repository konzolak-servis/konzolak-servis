<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NabidkaPolozka extends Model
{
    protected $table = 'nabidka_polozky';

    protected $guarded = [];

    protected $casts = [
        'mnozstvi' => 'decimal:3',
        'cena' => 'decimal:2',
        'cena_celkem' => 'decimal:2',
        'cena_nova' => 'decimal:2',
        'cena_bazar' => 'decimal:2',
        'naklad_interni' => 'decimal:2',
    ];

    public const VARIANTY = ['nova' => 'Nový díl', 'bazar' => 'Bazar / použité'];

    protected static function booted(): void
    {
        static::saving(function (NabidkaPolozka $p) {
            // účtovaná cena = podle zvolené varianty (nová / bazar), fallback na ručně zadanou
            $dle = $p->varianta === 'bazar'
                ? ($p->cena_bazar ?? $p->cena_nova)
                : ($p->cena_nova ?? $p->cena_bazar);

            if ($dle !== null) {
                $p->cena = $dle;
            }

            $p->cena_celkem = round((float) $p->mnozstvi * (float) $p->cena, 2);
        });

        static::saved(fn (NabidkaPolozka $p) => $p->nabidka?->prepocti());
        static::deleted(fn (NabidkaPolozka $p) => $p->nabidka?->prepocti());
    }

    public function nabidka(): BelongsTo
    {
        return $this->belongsTo(Nabidka::class, 'nabidka_id');
    }

    /** Interní marže (rozdíl mezi účtovanou cenou a nákladem). */
    public function getMarzeAttribute(): ?float
    {
        return $this->naklad_interni !== null
            ? round((float) $this->cena - (float) $this->naklad_interni, 2)
            : null;
    }
}
