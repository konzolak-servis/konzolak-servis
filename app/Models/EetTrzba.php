<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jedna datová zpráva o evidované tržbě EET 2.0 (případně storno – záporná částka). */
class EetTrzba extends Model
{
    protected $table = 'eet_trzby';

    protected $guarded = [];

    protected $casts = [
        'celk_trzba' => 'decimal:2',
        'storno' => 'boolean',
        'overeni' => 'boolean',
        'varovani' => 'array',
        'odeslano_at' => 'datetime',
    ];

    public const STAVY = [
        'ceka' => 'Čeká na odeslání',
        'odeslano' => 'Odesláno',
        'chyba' => 'Chyba',
    ];

    public function denik(): BelongsTo
    {
        return $this->belongsTo(PenezniDenik::class, 'penezni_denik_id');
    }

    /** Zákonná lhůta pro dodatečné odeslání (48 h od tržby) se blíží / uplynula. */
    public function poLhute(): bool
    {
        return $this->stav !== 'odeslano' && $this->created_at && $this->created_at->lt(now()->subHours(48));
    }
}
