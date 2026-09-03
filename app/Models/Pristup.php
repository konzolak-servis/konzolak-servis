<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Pristup extends Model
{
    protected $table = 'pristupy';

    protected $guarded = [];

    protected $casts = [
        'heslo' => 'encrypted',          // šifrováno pomocí APP_KEY
        'platnost_do' => 'date',
        'castka' => 'decimal:2',
        'aktivni' => 'boolean',
        'pripominka_dni' => 'integer',
    ];

    public const KATEGORIE = [
        'hosting' => 'Hosting / server',
        'domena' => 'Doména',
        'email' => 'E-mail',
        'eshop' => 'E-shop / web',
        'ucet' => 'Účet / banka',
        'software' => 'Software / licence',
        'jine' => 'Jiné',
    ];

    /** Počet dní do konce platnosti (záporné = po splatnosti). */
    public function dniDoKonce(): ?int
    {
        return $this->platnost_do
            ? (int) Carbon::today()->diffInDays($this->platnost_do, false)
            : null;
    }

    /** Blíží se / prošlo (podle připomínky). */
    public function jeNaSpadnuti(): bool
    {
        $d = $this->dniDoKonce();

        return $d !== null && $d <= $this->pripominka_dni;
    }
}
