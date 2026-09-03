<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZakazkaPolozka extends Model
{
    protected $table = 'zakazka_polozky';

    protected $guarded = [];

    protected $casts = [
        'mnozstvi' => 'decimal:3',
        'cena_ks' => 'decimal:2',
        'cena_celkem' => 'decimal:2',
        'uctovat' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Dopočet řádku a přepočet součtu zakázky.
        static::saving(function (ZakazkaPolozka $p) {
            $p->cena_celkem = round((float) $p->mnozstvi * (float) $p->cena_ks, 2);
        });

        static::saved(fn (ZakazkaPolozka $p) => $p->zakazka?->prepocti());

        // Smazání řádku materiálu vrátí kusy zpět na sklad.
        static::deleting(function (ZakazkaPolozka $p) {
            if ($p->typ === 'material' && $p->sklad_polozka_id && $p->skladPolozka) {
                $p->skladPolozka->vraceni((float) $p->mnozstvi, [
                    'zdroj' => 'zakazka',
                    'zakazka_id' => $p->zakazka_id,
                ]);
            }
        });

        static::deleted(fn (ZakazkaPolozka $p) => $p->zakazka?->prepocti());
    }

    public function zakazka(): BelongsTo
    {
        return $this->belongsTo(Zakazka::class, 'zakazka_id');
    }

    public function skladPolozka(): BelongsTo
    {
        return $this->belongsTo(SkladPolozka::class, 'sklad_polozka_id');
    }
}
