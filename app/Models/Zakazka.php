<?php

namespace App\Models;

use App\Support\Cisla;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Zakazka extends Model
{
    protected $table = 'zakazky';

    protected $guarded = [];

    protected $casts = [
        'datum_prijeti' => 'date',
        'datum_vyrizeni' => 'date',
        'zaloha_datum' => 'date',
        'predpokladana_cena' => 'decimal:2',
        'zaloha' => 'decimal:2',
        'cena_celkem' => 'decimal:2',
        'zaruka_mesice' => 'integer',
        'dil_objednany' => 'boolean',
        'zaloha_v_prijmech' => 'boolean',
        'fotky' => 'array',
    ];

    public const ZPUSOBY_UHRADY = ['hotove' => 'Hotově', 'ucet' => 'Na účet'];

    /** Stavy, které se počítají jako dokončené. */
    public const STAVY_HOTOVO = ['hotovo', 'vydano'];

    public function jeHotovo(): bool
    {
        return in_array($this->stav, self::STAVY_HOTOVO, true);
    }

    /** Barva pro kalendář / odznaky podle stavu. */
    public function stavBarva(): string
    {
        return match ($this->stav) {
            'prijato' => 'amber',
            'diagnostika' => 'sky',
            'ceka_na_dil' => 'orange',
            'hotovo' => 'emerald',
            'vydano' => 'slate',
            'nerentabilni' => 'red',
            'storno' => 'zinc',
            default => 'gray',
        };
    }

    public const STAVY = [
        'prijato' => 'Přijato',
        'diagnostika' => 'Diagnostika',
        'ceka_na_dil' => 'Čeká na díl',
        'hotovo' => 'Hotovo',
        'vydano' => 'Vydáno',
        'nerentabilni' => 'Nerentabilní',
        'storno' => 'Storno',
    ];

    protected static function booted(): void
    {
        static::creating(function (Zakazka $z) {
            $z->cislo ??= Cisla::dalsi('zakazka', 'SL');
            $z->datum_prijeti ??= now()->toDateString();
            $z->zaruka_mesice ??= (int) (Firma::get()->zaruka_mesice ?: 3);
        });

        static::saved(function (Zakazka $z) {
            $z->synchronizujZalohu();
            $z->synchronizujPrijem();
        });
        static::deleted(function (Zakazka $z) {
            PenezniDenik::where('zdroj_id', $z->id)
                ->whereIn('zdroj', ['zakazka', 'zakazka_zaloha'])->delete();
        });
    }

    /** Zakázky, u kterých zákazník zaplatil (příjem do deníku). */
    public const STAVY_ZAPLACENO = ['vydano', 'nerentabilni'];

    /** Přijatá záloha = příjem v peněžním deníku k datu jejího přijetí. */
    public function synchronizujZalohu(): void
    {
        $klic = ['zdroj' => 'zakazka_zaloha', 'zdroj_id' => $this->id];

        if ($this->zaloha > 0 && $this->zaloha_v_prijmech) {
            PenezniDenik::updateOrCreate($klic, [
                'datum' => ($this->zaloha_datum ?? $this->datum_prijeti ?? now())->toDateString(),
                'typ' => 'prijem',
                'popis' => 'Záloha ' . $this->cislo
                    . ($this->zakaznik ? ' – ' . $this->zakaznik->nazev : ''),
                'castka' => (float) $this->zaloha,
                'kategorie' => 'Servis – záloha',
                'zpusob' => $this->zpusob_uhrady,
            ]);
        } else {
            PenezniDenik::where($klic)->delete();
        }
    }

    /** Uzavřená zakázka = příjem (doplatek, pokud byla záloha už v příjmech). */
    public function synchronizujPrijem(): void
    {
        $klic = ['zdroj' => 'zakazka', 'zdroj_id' => $this->id];

        $zalohaZapoctena = $this->zaloha > 0 && $this->zaloha_v_prijmech;
        $castka = (float) $this->cena_celkem - ($zalohaZapoctena ? (float) $this->zaloha : 0);

        if (in_array($this->stav, self::STAVY_ZAPLACENO, true) && $castka > 0) {
            PenezniDenik::updateOrCreate($klic, [
                'datum' => ($this->datum_vyrizeni ?? now())->toDateString(),
                'typ' => 'prijem',
                'popis' => ($zalohaZapoctena ? 'Doplatek ' : 'Oprava ') . $this->cislo
                    . ($this->zakaznik ? ' – ' . $this->zakaznik->nazev : ''),
                'castka' => $castka,
                'kategorie' => 'Servis',
                'zpusob' => $this->zpusob_uhrady,
            ]);
        } else {
            PenezniDenik::where($klic)->delete();
        }
    }

    /** Datum konce záruky (datum vyřízení + záruční měsíce). */
    public function zarukaDo(): ?\Illuminate\Support\Carbon
    {
        return $this->datum_vyrizeni?->copy()->addMonthsNoOverflow($this->zaruka_mesice ?: 0);
    }

    public function vZaruce(): bool
    {
        $do = $this->zarukaDo();

        return $do !== null && $do->isFuture();
    }

    public function reklamaceK(): BelongsTo
    {
        return $this->belongsTo(Zakazka::class, 'reklamace_k_id');
    }

    public function reklamace(): HasMany
    {
        return $this->hasMany(Zakazka::class, 'reklamace_k_id');
    }

    public function zakaznik(): BelongsTo
    {
        return $this->belongsTo(Zakaznik::class, 'zakaznik_id');
    }

    public function zarizeni(): BelongsTo
    {
        return $this->belongsTo(Zarizeni::class, 'zarizeni_id');
    }

    public function polozky(): HasMany
    {
        return $this->hasMany(ZakazkaPolozka::class, 'zakazka_id');
    }

    public function faktura(): HasOne
    {
        return $this->hasOne(Faktura::class, 'zakazka_id');
    }

    public function pohyby(): HasMany
    {
        return $this->hasMany(SkladovyPohyb::class, 'zakazka_id');
    }

    public function getStavNazevAttribute(): string
    {
        return self::STAVY[$this->stav] ?? $this->stav;
    }

    /**
     * Přepočítá cena_celkem ze součtu účtovaných řádků.
     * Pokud žádné řádky nejsou, ponechá ručně zadanou cenu.
     */
    public function prepocti(): void
    {
        if ($this->polozky()->exists()) {
            $this->cena_celkem = $this->polozky()->where('uctovat', true)->sum('cena_celkem');
            $this->saveQuietly();
        }

        $this->synchronizujPrijem();
    }

    /** Evidenční hodnota materiálu ze skladu (neúčtovaného) – jen pro přehled. */
    public function hodnotaMaterialu(): float
    {
        return (float) $this->polozky()->where('typ', 'material')->sum('cena_celkem');
    }
}
