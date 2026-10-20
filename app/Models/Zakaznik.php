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

    /**
     * Najde zákazníka (firmu) podle IČO; když ještě není, založí ho z údajů v ARES.
     * Vrací [Zakaznik, bool $novy]. Při chybě registru vyhodí \RuntimeException.
     */
    public static function najdiNeboZalozPodleIco(string $ico): array
    {
        $norm = \App\Support\Ares::normalizuj($ico);
        if ($norm && ($existujici = static::where('ico', $norm)->orWhere('ico', ltrim($norm, '0'))->first())) {
            return [$existujici, false];
        }

        $a = \App\Support\Ares::najdi($ico);

        // mohl už být uložený s jiným zápisem IČO (mezery apod.)
        $existujici = static::whereRaw("REPLACE(ico, ' ', '') = ?", [$a['ico']])->first();
        if ($existujici) {
            return [$existujici, false];
        }

        return [static::create([
            'typ' => 'firma',
            'firma_nazev' => $a['nazev'],
            'ico' => $a['ico'],
            'dic' => $a['dic'],
            'ulice' => $a['ulice'],
            'psc' => $a['psc'],
            'mesto' => $a['mesto'],
        ]), true];
    }
}
