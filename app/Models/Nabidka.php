<?php

namespace App\Models;

use App\Support\Cisla;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nabidka extends Model
{
    protected $table = 'nabidky';

    protected $guarded = [];

    protected $casts = [
        'datum' => 'date',
        'platnost_do' => 'date',
        'zaloha' => 'decimal:2',
        'celkem' => 'decimal:2',
    ];

    public const STAVY = [
        'nova' => 'Nová',
        'prijata' => 'Přijata',
        'zamitnuta' => 'Zamítnuta',
        'prevedena_na_fakturu' => 'Převedena na fakturu',
    ];

    protected static function booted(): void
    {
        static::creating(function (Nabidka $n) {
            $n->cislo ??= Cisla::dalsi('nabidka', 'NAB');
            $n->datum ??= now()->toDateString();
            $n->platnost_do ??= now()->addDays(14)->toDateString();
        });
    }

    public function zakaznik(): BelongsTo
    {
        return $this->belongsTo(Zakaznik::class, 'zakaznik_id');
    }

    public function polozky(): HasMany
    {
        return $this->hasMany(NabidkaPolozka::class, 'nabidka_id');
    }

    public function faktura(): BelongsTo
    {
        return $this->belongsTo(Faktura::class, 'faktura_id');
    }

    public function prepocti(): void
    {
        $this->celkem = $this->polozky()->sum('cena_celkem');
        $this->saveQuietly();
    }
}
