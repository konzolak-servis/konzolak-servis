<?php

namespace App\Models;

use App\Support\Cisla;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faktura extends Model
{
    protected $table = 'faktury';

    protected $guarded = [];

    protected $casts = [
        'datum_vystaveni' => 'date',
        'datum_splatnosti' => 'date',
        'datum_uhrady' => 'date',
        'celkem' => 'decimal:2',
        'uhrazeno' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Faktura $f) {
            $rok = (int) date('Y', strtotime($f->datum_vystaveni ?? now()));
            $f->cislo ??= Cisla::dalsi('faktura', 'FA', $rok);
            $f->variabilni_symbol ??= preg_replace('/\D/', '', $f->cislo);
            $f->datum_vystaveni ??= now()->toDateString();
            $f->datum_splatnosti ??= now()->addDays((int) (Firma::get()->splatnost_dni ?: 14))->toDateString();
        });

        static::saved(fn (Faktura $f) => $f->synchronizujPrijem());
        static::deleted(fn (Faktura $f) => PenezniDenik::where('zdroj', 'faktura')->where('zdroj_id', $f->id)->delete());
    }

    /** Uhrazená faktura = příjem v peněžním deníku (jeden záznam na fakturu). */
    public function synchronizujPrijem(): void
    {
        $klic = ['zdroj' => 'faktura', 'zdroj_id' => $this->id];

        // částku bereme vždy čerstvě ze součtu řádků (in-memory hodnota může být stará)
        $castka = (float) $this->polozky()->sum('cena_celkem');

        if ($this->uhrazeno && $castka > 0) {
            PenezniDenik::updateOrCreate($klic, [
                'datum' => ($this->datum_uhrady ?? $this->datum_vystaveni ?? now())->toDateString(),
                'typ' => 'prijem',
                'popis' => 'Faktura ' . $this->cislo
                    . ($this->zakaznik ? ' – ' . $this->zakaznik->nazev : ''),
                'castka' => $castka,
                'kategorie' => 'Servis',
            ]);
        } else {
            PenezniDenik::where($klic)->delete();
        }
    }

    public function zakaznik(): BelongsTo
    {
        return $this->belongsTo(Zakaznik::class, 'zakaznik_id');
    }

    public function zakazka(): BelongsTo
    {
        return $this->belongsTo(Zakazka::class, 'zakazka_id');
    }

    public function polozky(): HasMany
    {
        return $this->hasMany(FakturaPolozka::class, 'faktura_id');
    }

    public function prepocti(): void
    {
        $this->celkem = $this->polozky()->sum('cena_celkem');
        $this->saveQuietly();
        $this->synchronizujPrijem();
    }
}
