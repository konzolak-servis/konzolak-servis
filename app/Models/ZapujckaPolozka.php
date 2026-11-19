<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZapujckaPolozka extends Model
{
    protected $table = 'zapujcka_polozky';

    protected $guarded = [];

    protected $casts = [
        'hodnota' => 'decimal:2',
    ];

    public function zapujcka(): BelongsTo
    {
        return $this->belongsTo(Zapujcka::class, 'zapujcka_id');
    }

    public function zapujcniZarizeni(): BelongsTo
    {
        return $this->belongsTo(ZapujcniZarizeni::class, 'zapujcni_zarizeni_id');
    }
}
