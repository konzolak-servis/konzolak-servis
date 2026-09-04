<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zarizeni extends Model
{
    protected $table = 'zarizeni';

    protected $guarded = [];

    /** Jednotný číselník platforem – viz App\Support\Platformy. */
    public const KATEGORIE = \App\Support\Platformy::HODNOTY;

    public function zakaznik(): BelongsTo
    {
        return $this->belongsTo(Zakaznik::class, 'zakaznik_id');
    }

    public function zakazky(): HasMany
    {
        return $this->hasMany(Zakazka::class, 'zarizeni_id');
    }

    public function getPopisAttribute(): string
    {
        return trim($this->oznaceni . ($this->seriove_cislo ? "  (SN: {$this->seriove_cislo})" : ''));
    }
}
