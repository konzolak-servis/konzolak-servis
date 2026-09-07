<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Zprava extends Model
{
    protected $table = 'zpravy';

    protected $guarded = [];

    protected $casts = [
        'datum' => 'datetime',
        'precteno_at' => 'datetime',
        'spam' => 'boolean',
        'prilohy' => 'array',
    ];

    protected static function booted(): void
    {
        // Při smazání zprávy ukliď i její uložené přílohy z disku.
        static::deleting(function (Zprava $zprava) {
            if (is_array($zprava->prilohy) && $zprava->prilohy !== []) {
                Storage::disk('local')->deleteDirectory('posta/' . $zprava->id);
            }
        });
    }

    public function zakazka(): BelongsTo
    {
        return $this->belongsTo(Zakazka::class);
    }

    public function zakaznik(): BelongsTo
    {
        return $this->belongsTo(Zakaznik::class);
    }

    public function scopePrichozi($q)
    {
        return $q->where('smer', 'in');
    }

    public function scopeNeprectene($q)
    {
        return $q->where('smer', 'in')->whereNull('precteno_at')->where('spam', false);
    }

    public function jePrectena(): bool
    {
        return $this->precteno_at !== null;
    }

    /** Krátký náhled těla pro seznam. */
    public function nahled(int $delka = 120): string
    {
        $text = $this->telo_text ?: strip_tags((string) $this->telo_html);
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return mb_strlen($text) > $delka ? mb_substr($text, 0, $delka) . '…' : $text;
    }
}
