<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkladovyPohyb extends Model
{
    protected $table = 'skladove_pohyby';

    protected $guarded = [];

    protected $casts = [
        'mnozstvi' => 'decimal:3',
        'cena_ks' => 'decimal:2',
        'datum' => 'date',
    ];

    public function skladPolozka(): BelongsTo
    {
        return $this->belongsTo(SkladPolozka::class, 'sklad_polozka_id');
    }

    public function nakup(): BelongsTo
    {
        return $this->belongsTo(Nakup::class, 'nakup_id');
    }

    public function zakazka(): BelongsTo
    {
        return $this->belongsTo(Zakazka::class, 'zakazka_id');
    }
}
