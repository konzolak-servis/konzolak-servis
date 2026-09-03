<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FakturaPolozka extends Model
{
    protected $table = 'faktura_polozky';

    protected $guarded = [];

    protected $casts = [
        'mnozstvi' => 'decimal:3',
        'cena' => 'decimal:2',
        'cena_celkem' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (FakturaPolozka $p) {
            $p->cena_celkem = round((float) $p->mnozstvi * (float) $p->cena, 2);
        });

        static::saved(fn (FakturaPolozka $p) => $p->faktura?->prepocti());
        static::deleted(fn (FakturaPolozka $p) => $p->faktura?->prepocti());
    }

    public function faktura(): BelongsTo
    {
        return $this->belongsTo(Faktura::class, 'faktura_id');
    }
}
