<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenikPolozka extends Model
{
    protected $table = 'cenik';

    protected $guarded = [];

    protected $casts = [
        'cena' => 'decimal:2',
        'aktivni' => 'boolean',
        'poradi' => 'integer',
    ];
}
