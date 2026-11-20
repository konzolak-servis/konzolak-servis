<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Firma extends Model
{
    protected $table = 'firma';

    protected $guarded = [];

    protected $casts = [
        'platce_dph' => 'boolean',
        'splatnost_dni' => 'integer',
        'zaruka_mesice' => 'integer',
        'eet_aktivni' => 'boolean',
        'eet_overeni' => 'boolean',
        'eet_od' => 'date',
    ];

    /** Heslo k pokladnímu certifikátu se nikdy neposílá do formuláře ani do JSONu. */
    protected $hidden = ['eet_cert_heslo'];

    /** Vždy vrátí jediný záznam nastavení (vytvoří prázdný, pokud chybí). */
    public static function get(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
