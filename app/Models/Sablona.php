<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sablona extends Model
{
    protected $table = 'sablony';

    protected $guarded = [];

    protected $casts = [
        'aktivni' => 'boolean',
        'poradi' => 'integer',
    ];

    public const TYPY = [
        'zavada' => 'Závada / popis od zákazníka',
        'reseni' => 'Řešení / provedené práce',
        'poznamka' => 'Poznámka',
    ];

    public function scopeAktivni($q)
    {
        return $q->where('aktivni', true)->orderBy('poradi')->orderBy('nazev');
    }
}
