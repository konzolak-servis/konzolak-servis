<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zakaznik extends Model
{
    protected $table = 'zakaznici';

    protected $guarded = [];

    protected $casts = [
        'typ' => 'string',
    ];

    public function zarizeni(): HasMany
    {
        return $this->hasMany(Zarizeni::class, 'zakaznik_id');
    }

    public function zakazky(): HasMany
    {
        return $this->hasMany(Zakazka::class, 'zakaznik_id');
    }

    public function faktury(): HasMany
    {
        return $this->hasMany(Faktura::class, 'zakaznik_id');
    }

    /** Zobrazované jméno – u firmy název firmy, u osoby jméno. */
    public function getNazevAttribute(): string
    {
        return $this->typ === 'firma'
            ? ($this->firma_nazev ?: $this->jmeno ?: 'Bez názvu')
            : ($this->jmeno ?: $this->firma_nazev ?: 'Bez názvu');
    }

    public function getAdresaRadekAttribute(): string
    {
        return trim(collect([$this->ulice, trim(($this->psc ?? '') . ' ' . ($this->mesto ?? ''))])
            ->filter()->implode(', '));
    }
}
