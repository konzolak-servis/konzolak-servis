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

        // Výkup je hotová věc (peníze reálně vyplacené) hned při založení záznamu –
        // v UI není žádný samostatný krok „potvrzení"/vyřízení před tímhle okamžikem.
        static::created(fn (Obchod $o) => $o->zapisNakupDoDeniku());
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
     * „Vyřídit" výkup – potvrdí záznam (kvůli dokladu o výkupu) a zapíše výdaj
     * do peněžního deníku, ať je vidět reálný pohyb peněz. Bazar dál NEzapisuje
     * do dílenského skladu (jde o samostatnou bazarovou evidenci).
     */
    public function vyridit(): void
    {
        if (! $this->vyrizeno) {
            $this->update(['vyrizeno' => true]);
        }
        $this->zapisNakupDoDeniku();
    }

    /** Prodej zařízení – zapíše prodejní cenu, datum a příjem do peněžního deníku. */
    public function prodat(float $cena, ?string $datum = null, ?string $komu = null): void
    {
        $this->update([
            'prodano' => true,
            'prodejni_cena' => $cena,
            'prodej_datum' => $datum ?: now()->toDateString(),
            'prodej_komu' => $komu,
            'vyrizeno' => true,
        ]);
        $this->zapisProdejDoDeniku();
    }

    /**
     * Zapíše výdaj za výkup do peněžního deníku (kategorie „Bazar"), pokud tam
     * ještě není. Bezpečné volat opakovaně – nikdy nevytvoří duplicitní záznam.
     * Vrací true, pokud právě něco nově zapsal.
     */
    public function zapisNakupDoDeniku(): bool
    {
        if ((float) $this->cena <= 0) {
            return false;
        }

        if (PenezniDenik::where('zdroj', 'obchod')->where('zdroj_id', $this->id)
            ->where('typ', 'vydej')->exists()) {
            return false;
        }

        PenezniDenik::create([
            'datum' => $this->datum?->toDateString() ?? now()->toDateString(),
            'typ' => 'vydej',
            'popis' => 'Bazar – výkup ' . $this->cislo . ($this->nazev ? ' – ' . $this->nazev : ''),
            'castka' => (float) $this->cena,
            'kategorie' => 'Bazar',
            'zpusob' => $this->zpusob_uhrady,
            'zdroj' => 'obchod',
            'zdroj_id' => $this->id,
        ]);

        return true;
    }

    /**
     * Zapíše příjem z prodeje do peněžního deníku (kategorie „Bazar"), pokud tam
     * ještě není. Bezpečné volat opakovaně – nikdy nevytvoří duplicitní záznam.
     * Vrací true, pokud právě něco nově zapsal.
     *
     * POZOR: náklady na vložené díly (naklady_dilu) se sem NEzapisují zvlášť –
     * díl ze skladu se do deníku propsal už při jeho nákupu do skladu, ručně
     * přidaný náklad zatím nikam propsaný není (řeší se jinde, případ od případu).
     */
    public function zapisProdejDoDeniku(): bool
    {
        if (! $this->prodano || (float) $this->prodejni_cena <= 0) {
            return false;
        }

        if (PenezniDenik::where('zdroj', 'obchod')->where('zdroj_id', $this->id)
            ->where('typ', 'prijem')->exists()) {
            return false;
        }

        PenezniDenik::create([
            'datum' => $this->prodej_datum?->toDateString() ?? now()->toDateString(),
            'typ' => 'prijem',
            'popis' => 'Bazar – prodej ' . $this->cislo . ($this->nazev ? ' – ' . $this->nazev : '')
                . ($this->prodej_komu ? ' (' . $this->prodej_komu . ')' : ''),
            'castka' => (float) $this->prodejni_cena,
            'kategorie' => 'Bazar',
            'zdroj' => 'obchod',
            'zdroj_id' => $this->id,
        ]);

        return true;
    }
}
