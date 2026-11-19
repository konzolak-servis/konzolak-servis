<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Vlastní zařízení servisu určené k zapůjčení zákazníkům (náhradní konzole, ovladače…). */
class ZapujcniZarizeni extends Model
{
    protected $table = 'zapujcni_zarizeni';

    protected $guarded = [];

    protected $casts = [
        'hodnota' => 'decimal:2',
        'kauce' => 'decimal:2',
        'aktivni' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Vždy zkontrolovat, jestli zařízení není v bazaru (podle sériového čísla) a spárovat ho.
        static::saving(function (ZapujcniZarizeni $d) {
            if (! $d->obchod_id || $d->isDirty('seriove_cislo')) {
                $d->sparujSBazarem();
            }
        });
    }

    public function polozky(): HasMany
    {
        return $this->hasMany(ZapujckaPolozka::class, 'zapujcni_zarizeni_id');
    }

    public function obchod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Obchod::class, 'obchod_id');
    }

    /** Sériové číslo bez mezer, pomlček a velikosti písmen. */
    public static function normSn(?string $sn): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $sn));
    }

    /** Najde v bazaru neprodaný kus se stejným sériovým číslem a propojí ho (ruční výběr se nepřepisuje). */
    public function sparujSBazarem(): void
    {
        $sn = self::normSn($this->seriove_cislo);
        if (strlen($sn) < 4 || ($this->obchod_id && (! $this->isDirty('seriove_cislo') || $this->isDirty('obchod_id')))) {
            return;
        }

        $o = Obchod::query()->whereNotNull('seriove_cislo')->get(['id', 'seriove_cislo', 'prodano'])
            ->first(fn ($o) => self::normSn($o->seriove_cislo) === $sn);
        if ($o) {
            $this->obchod_id = $o->id;
        }
    }

    /** Položka běžící zápůjčky, ve které je zařízení právě venku (nebo null = volné). */
    public function aktualniPolozka(): ?ZapujckaPolozka
    {
        return $this->polozky()->whereHas('zapujcka', fn ($q) => $q->where('stav', 'zapujceno'))
            ->with('zapujcka.zakaznik')->latest('id')->first();
    }

    public function scopeVolne($query, ?int $krome = null)
    {
        return $query->where('aktivni', true)
            ->where(fn ($q) => $q->whereDoesntHave('polozky', fn ($p) => $p->whereHas('zapujcka', fn ($z) => $z->where('stav', 'zapujceno')))
                ->when($krome, fn ($w) => $w->orWhere('id', $krome)));
    }

    public function getPopisekAttribute(): string
    {
        return $this->nazev . ($this->seriove_cislo ? ' · SN ' . $this->seriove_cislo : '');
    }
}
