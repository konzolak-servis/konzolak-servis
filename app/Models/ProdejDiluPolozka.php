<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdejDiluPolozka extends Model
{
    protected $table = 'prodej_dilu_polozky';

    protected $guarded = [];

    protected $casts = [
        'mnozstvi' => 'decimal:3',
        'cena_ks' => 'decimal:2',
        'naklad_ks' => 'decimal:2',
        'celkem' => 'decimal:2',
    ];

    public function prodej(): BelongsTo
    {
        return $this->belongsTo(ProdejDilu::class, 'prodej_dilu_id');
    }

    public function skladPolozka(): BelongsTo
    {
        return $this->belongsTo(SkladPolozka::class, 'sklad_polozka_id');
    }
}
