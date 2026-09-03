<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zarizeni extends Model
{
    protected $table = 'zarizeni';

    protected $guarded = [];

    public const KATEGORIE = [
        'PS5' => 'PlayStation 5',
        'PS4' => 'PlayStation 4',
        'PS3' => 'PlayStation 3',
        'Switch' => 'Nintendo Switch',
        'Xbox' => 'Xbox',
        'ovladac' => 'Ovladač',
        'PC' => 'PC / notebook',
        'jine' => 'Jiné',
    ];

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
