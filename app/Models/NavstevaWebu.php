<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Unikátní návštěvník veřejného webu za jeden den (viz middleware PocitadloNavstev). */
class NavstevaWebu extends Model
{
    protected $table = 'navstevy_webu';

    protected $guarded = [];

    protected $casts = [
        'den' => 'date',
        'zobrazeni' => 'integer',
    ];
}
