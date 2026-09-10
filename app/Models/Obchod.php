<?php

namespace App\Models;

use App\Support\Cisla;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obchod extends Model
{
    protected $table = 'obchody';

    protected $guarded = [];

    protected $casts = [
        'datum' => 'date',
        'cena' => 'decimal:2',
        'prodejni_cena' => 'decimal:2',
        'prodej_datum' => 'date',
        'prodano' => 'boolean',
        'vyrizeno' => 'boolean',
    ];

    /** Jednotný číselník platforem – viz App\Support\Platformy. */
    public const KATEGORIE = \App\Support\Platformy::HODNOTY;

    public const ZPUSOBY_UHRADY = ['hotove' => 'Hotově', 'ucet' => 'Na účet'];

    protected static function booted(): void
    {
        static::creating(function (Obchod $o) {
            $o->datum ??= now()->toDateString();
            $o->typ ??= 'vykup';
            $o->cislo ??= $o->typ === 'vykup'
                ? Cisla::dalsi('vykup', 'VYK')
                : Cisla::dalsi('prodej', 'PRD');
        });
    }

    public function skladPolozka(): BelongsTo
    {
        return $this->belongsTo(SkladPolozka::class, 'sklad_polozka_id');
    }

    /** Díly a náklady vložené do zařízení (investice před prodejem). */
    public function naklady(): HasMany
    {
        return $this->hasMany(BazarNaklad::class, 'obchod_id');
    }

    public function getTypNazevAttribute(): string
    {
        return $this->typ === 'vykup' ? 'Výkup' : 'Prodej';
    }

    /** Součet nákladů na díly + ruční náklady vložené do zařízení. */
    public function getNakladyDiluAttribute(): float
    {
        return round((float) $this->naklady->sum(fn (BazarNaklad $n) => $n->cena_celkem), 2);
    }

    /** Celková investice = pořizovací cena + díly + náklady. */
    public function getNakladyCelkemAttribute(): float
    {
        return round((float) $this->cena + $this->naklady_dilu, 2);
    }

    /** Zisk po prodeji (null, dokud není prodáno). */
    public function getZiskAttribute(): ?float
    {
        return $this->prodano
            ? round((float) $this->prodejni_cena - $this->naklady_celkem, 2)
            : null;
    }

    /**
     * „Vyřídit" výkup – jen potvrdí záznam kvůli dokladu o výkupu.
     * Bazar je interní: NEzapisuje do peněžního deníku ani do dílenského skladu.
     */
    public function vyridit(): void
    {
        if (! $this->vyrizeno) {
            $this->update(['vyrizeno' => true]);
        }
    }

    /** Prodej zařízení – zapíše prodejní cenu a datum (bez deníku). */
    public function prodat(float $cena, ?string $datum = null, ?string $komu = null): void
    {
        $this->update([
            'prodano' => true,
            'prodejni_cena' => $cena,
            'prodej_datum' => $datum ?: now()->toDateString(),
            'prodej_komu' => $komu,
            'vyrizeno' => true,
        ]);
    }
}
