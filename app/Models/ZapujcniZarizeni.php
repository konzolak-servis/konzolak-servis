<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Vlastní zařízení servisu určené k zapůjčení zákazníkům (náhradní konzole, ovladače…). */
class ZapujcniZarizeni extends Model
{
    protected $table = 'zapujcni_zarizeni';

    protected $guarded = [];

    protected $casts = [
        'hodnota' => 'decimal:2',
        'kauce' => 'decimal:2',
        'aktivni' => 'boolean',
    ];

    public function polozky(): HasMany
    {
        return $this->hasMany(ZapujckaPolozka::class, 'zapujcni_zarizeni_id');
    }

    /** Položka běžící zápůjčky, ve které je zařízení právě venku (nebo null = volné). */
    public function aktualniPolozka(): ?ZapujckaPolozka
    {
        return $this->polozky()->whereHas('zapujcka', fn ($q) => $q->where('stav', 'zapujceno'))
            ->with('zapujcka.zakaznik')->latest('id')->first();
    }

    public function scopeVolne($query, ?int $krome = null)
    {
        return $query->where('aktivni', true)
            ->where(fn ($q) => $q->whereDoesntHave('polozky', fn ($p) => $p->whereHas('zapujcka', fn ($z) => $z->where('stav', 'zapujceno')))
                ->when($krome, fn ($w) => $w->orWhere('id', $krome)));
    }

    public function getPopisekAttribute(): string
    {
        return $this->nazev . ($this->seriove_cislo ? ' · SN ' . $this->seriove_cislo : '');
    }
}
