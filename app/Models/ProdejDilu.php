<?php

namespace App\Models;

use App\Support\Cisla;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Prodej dílů ze skladu zákazníkovi mimo zakázku.
 * Při dokončení: výdej ze skladu + příjem do peněžního deníku (→ EET u hotovosti / QR na místě).
 * Smazání = storno: díly zpět na sklad, příjem z deníku pryč (EET storno automaticky).
 */
class ProdejDilu extends Model
{
    protected $table = 'prodeje_dilu';

    protected $guarded = [];

    protected $casts = [
        'datum' => 'date',
        'celkem' => 'decimal:2',
    ];

    public const PLATBY = [
        'hotove' => 'Hotově',
        'qr_na_miste' => 'QR / převodem na místě',
        'ucet' => 'Převodem na dálku',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProdejDilu $p) {
            $p->cislo ??= Cisla::dalsi('prodej_dilu', 'PDL');
            $p->datum ??= now()->toDateString();
        });

        static::deleting(fn (ProdejDilu $p) => $p->stornovat());
    }

    public function zakaznik(): BelongsTo
    {
        return $this->belongsTo(Zakaznik::class, 'zakaznik_id');
    }

    public function polozky(): HasMany
    {
        return $this->hasMany(ProdejDiluPolozka::class, 'prodej_dilu_id');
    }

    public function kupujiciNazev(): string
    {
        return $this->zakaznik?->nazev ?: ($this->kupujici ?: 'zákazník');
    }

    /** Výdej ze skladu a zápis příjmu – volá se jednou po uložení prodeje s položkami. */
    public function dokoncit(): void
    {
        DB::transaction(function () {
            $celkem = 0;
            foreach ($this->polozky()->with('skladPolozka')->get() as $p) {
                $s = $p->skladPolozka;
                if ($s) {
                    $p->naklad_ks = (float) $s->cena_ks_prumer;
                    $s->vydej((float) $p->mnozstvi, ['zdroj' => 'prodej', 'poznamka' => 'Prodej ' . $this->cislo]);
                }
                $p->celkem = round((float) $p->mnozstvi * (float) $p->cena_ks, 2);
                $p->save();
                $celkem += (float) $p->celkem;
            }
            $this->update(['celkem' => $celkem]);

            if ($celkem > 0) {
                PenezniDenik::create([
                    'datum' => $this->datum->toDateString(),
                    'typ' => 'prijem',
                    'popis' => 'Prodej dílů ' . $this->cislo . ' – ' . $this->kupujiciNazev(),
                    'castka' => $celkem,
                    'kategorie' => 'Prodej dílů',
                    'zpusob' => $this->platba === 'hotove' ? 'hotove' : 'ucet',
                    'eet_na_miste' => $this->platba === 'qr_na_miste',
                    'kde' => $this->kupujiciNazev(),
                    'zdroj' => 'prodej_dilu',
                    'zdroj_id' => $this->id,
                ]);
            }
        });
    }

    /** Storno: díly zpět na sklad, příjem z deníku pryč. */
    public function stornovat(): void
    {
        foreach ($this->polozky()->with('skladPolozka')->get() as $p) {
            $p->skladPolozka?->vraceni((float) $p->mnozstvi, ['zdroj' => 'prodej', 'poznamka' => 'Storno prodeje ' . $this->cislo]);
        }
        PenezniDenik::where('zdroj', 'prodej_dilu')->where('zdroj_id', $this->id)->get()->each->delete();
    }

    public function marze(): float
    {
        return round($this->polozky->sum(fn ($p) => (float) $p->celkem - (float) $p->mnozstvi * (float) $p->naklad_ks), 2);
    }

    public function tiskUrl(): string
    {
        return route('tisk.prodej-dilu', ['prodej' => $this, 'soubor' => 'Doklad-o-prodeji-' . $this->cislo . '.pdf']);
    }
}
