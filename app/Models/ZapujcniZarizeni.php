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

            // Typ (šablona ceníku): když chybí, zkusit podle názvu; prázdná pole doplnit z typu.
            // (Ve starších migracích tabulka typů ještě neexistuje.)
            static $maTypy = false;
            $maTypy = $maTypy || \Illuminate\Support\Facades\Schema::hasTable('zapujcni_typy');
            if (! $maTypy) {
                return;
            }
            if (! $d->typ_id && ! $d->exists) {
                $d->typ_id = ZapujcniTyp::najdiPodleNazvu($d->nazev)?->id;
            }
            if ($d->typ_id && ($typ = ZapujcniTyp::find($d->typ_id))) {
                foreach (ZapujcniTyp::POLE as $pole) {
                    if (blank($d->{$pole}) && filled($typ->{$pole})) {
                        $d->{$pole} = $typ->{$pole};
                    }
                }
            }
        });
    }

    public function polozky(): HasMany
    {
        return $this->hasMany(ZapujckaPolozka::class, 'zapujcni_zarizeni_id');
    }

    public function typ(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ZapujcniTyp::class, 'typ_id');
    }

    public function obchod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Obchod::class, 'obchod_id');
    }

    public function skladPolozka(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SkladPolozka::class, 'sklad_polozka_id');
    }

    /** Odkud zařízení na zápůjční sklad přišlo (pro popisky). */
    public function puvodText(): ?string
    {
        return match (true) {
            (bool) $this->obchod_id => 'bazar ' . ($this->obchod?->cislo ?? ''),
            (bool) $this->sklad_polozka_id => 'sklad',
            default => null,
        };
    }

    /** Převod 1 ks ze skladu (nákupu) na zápůjční sklad – ze skladu se odepíše. */
    public static function zeSkladu(SkladPolozka $s): self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($s) {
            $s->vydej(1, ['zdroj' => 'zapujcni_sklad', 'poznamka' => 'Převod na zápůjční sklad']);

            return static::create([
                'sklad_polozka_id' => $s->id,
                'nazev' => $s->nazev,
                'hodnota' => (float) $s->cena_ks_prumer ?: null,
                'stav_popis' => 'nový / funkční',
                'aktivni' => true,
            ]);
        });
    }

    /**
     * Převod zpět: ze skladu (nákupu) se kus vrátí na sklad a záznam zmizí, z bazaru se kus vrátí do prodeje
     * (záznam se vyřadí, historie zápůjček zůstane). Zapůjčené zařízení vrátit nejde.
     */
    public function prevestZpet(): void
    {
        if ($this->aktualniPolozka()) {
            throw new \RuntimeException('Zařízení je právě zapůjčené – nejdřív ukonči zápůjčku.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () {
            if ($this->sklad_polozka_id && $this->skladPolozka) {
                $this->skladPolozka->vraceni(1, ['zdroj' => 'zapujcni_sklad', 'poznamka' => 'Vráceno ze zápůjčního skladu']);
                $this->polozky()->exists() ? $this->update(['aktivni' => false, 'sklad_polozka_id' => null]) : $this->delete();

                return;
            }
            $this->update(['aktivni' => false]);
        });
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
