<?php

namespace App\Models;

use App\Support\Cisla;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Obchod extends Model
{
    protected $table = 'obchody';

    protected $guarded = [];

    protected $casts = [
        'datum' => 'date',
        'cena' => 'decimal:2',
        'vyrizeno' => 'boolean',
    ];

    public const KATEGORIE = [
        'ovladac' => 'Ovladač',
        'konzole' => 'Konzole',
        'PC' => 'PC / notebook',
        'jine' => 'Jiné',
    ];

    public const ZPUSOBY_UHRADY = ['hotove' => 'Hotově', 'ucet' => 'Na účet'];

    protected static function booted(): void
    {
        static::creating(function (Obchod $o) {
            $o->datum ??= now()->toDateString();
            $o->cislo ??= $o->typ === 'vykup'
                ? Cisla::dalsi('vykup', 'VYK')
                : Cisla::dalsi('prodej', 'PRD');
        });

        static::deleted(function (Obchod $o) {
            PenezniDenik::where('zdroj', 'obchod')->where('zdroj_id', $o->id)->delete();
        });
    }

    public function skladPolozka(): BelongsTo
    {
        return $this->belongsTo(SkladPolozka::class, 'sklad_polozka_id');
    }

    public function getTypNazevAttribute(): string
    {
        return $this->typ === 'vykup' ? 'Výkup' : 'Prodej';
    }

    /**
     * Potvrzení obchodu:
     *  - výkup  → výdej peněz + naskladnění kusu (bazar)
     *  - prodej → příjem peněz + odečet ze skladu
     */
    public function vyridit(): void
    {
        if ($this->vyrizeno) {
            return;
        }

        DB::transaction(function () {
            if ($this->typ === 'vykup') {
                $sklad = $this->skladPolozka ?? SkladPolozka::create([
                    'nazev' => $this->nazev,
                    'kategorie' => 'Bazar',
                ]);
                $sklad->prijem(1, (float) $this->cena, [
                    'zdroj' => 'obchod',
                    'poznamka' => 'Výkup ' . $this->cislo,
                    'datum' => $this->datum->toDateString(),
                ]);

                PenezniDenik::create([
                    'datum' => $this->datum->toDateString(),
                    'typ' => 'vydej',
                    'popis' => 'Výkup ' . $this->cislo . ' – ' . $this->nazev,
                    'castka' => (float) $this->cena,
                    'kategorie' => 'Bazar – výkup',
                    'kde' => $this->protistrana_jmeno,
                    'zpusob' => $this->zpusob_uhrady,
                    'zdroj' => 'obchod',
                    'zdroj_id' => $this->id,
                ]);

                $this->update(['vyrizeno' => true, 'sklad_polozka_id' => $sklad->id]);
            } else {
                if ($this->skladPolozka) {
                    $this->skladPolozka->vydej(1, [
                        'zdroj' => 'obchod',
                        'poznamka' => 'Prodej ' . $this->cislo,
                    ]);
                }

                PenezniDenik::create([
                    'datum' => $this->datum->toDateString(),
                    'typ' => 'prijem',
                    'popis' => 'Prodej ' . $this->cislo . ' – ' . $this->nazev,
                    'castka' => (float) $this->cena,
                    'kategorie' => 'Bazar – prodej',
                    'zpusob' => $this->zpusob_uhrady,
                    'zdroj' => 'obchod',
                    'zdroj_id' => $this->id,
                ]);

                $this->update(['vyrizeno' => true]);
            }
        });
    }
}
