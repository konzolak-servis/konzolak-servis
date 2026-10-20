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
        'odeslano_datum' => 'date',
        'predpokladana_cena' => 'decimal:2',
        'zaloha' => 'decimal:2',
        'cena_celkem' => 'decimal:2',
        'cena_dopravy' => 'decimal:2',
        'postovne_naklad' => 'decimal:2',
        'dobirka' => 'boolean',
        'sleva_jen_prace' => 'boolean',
        'zaruka_mesice' => 'integer',
        'dil_objednany' => 'boolean',
        'zaloha_v_prijmech' => 'boolean',
        'fotky' => 'array',
    ];

    public const ZPUSOBY_UHRADY = ['hotove' => 'Hotově', 'ucet' => 'Na účet'];

    public const ZPUSOBY_VYDANI = ['osobne' => 'Osobně', 'odeslani' => 'Odeslání'];

    /** Stavy, které se počítají jako dokončené. */
    public const STAVY_HOTOVO = ['hotovo', 'vydano'];

    public const SLEVY = [0 => 'Bez slevy', 5 => '5 %', 10 => '10 %', 15 => '15 %', 20 => '20 %'];

    /** Částka slevy v Kč (procenta z ceny opravy; doprava se neslevňuje). */
    public function slevaCastka(): float
    {
        return round($this->slevaZaklad() * (int) $this->sleva_procent / 100, 2);
    }

    /**
     * Částka, ze které se sleva počítá: celá cena opravy, nebo – při zapnutém „jen na práci" –
     * jen účtované práce (bez účtovaných dílů/materiálu). Bez rozpisu položek se počítá z celé ceny.
     */
    public function slevaZaklad(): float
    {
        $celkem = (float) $this->cena_celkem;

        if (! $this->sleva_jen_prace || ! $this->exists || ! $this->polozky()->exists()) {
            return $celkem;
        }

        return (float) $this->polozky()->where('uctovat', true)->where('typ', '!=', 'material')->sum('cena_celkem');
    }

    /** Cena opravy po slevě (bez dopravy). */
    public function cenaPoSleve(): float
    {
        return round((float) $this->cena_celkem - $this->slevaCastka(), 2);
    }

    /** Kolik zákazník ještě doplácí (cena po slevě + doprava/dobírka − záloha), nikdy záporně. */
    public function doplatek(): float
    {
        return max($this->cenaPoSleve() + (float) $this->cena_dopravy - (float) $this->zaloha, 0);
    }

    /** Zásilka jde na dobírku – zákazník platí dopravci, nic se předem nehradí (bez QR platby). */
    /**
     * URL tisku (doklad | protokol | stitek). Poslední část cesty je název souboru s číslem zakázky,
     * takže „Uložit jako" v prohlížeči nabídne např. Servisni-protokol-SL-2026-0034.pdf.
     */
    public function tiskUrl(string $co, array $query = []): string
    {
        $nazvy = ['doklad' => 'Doklad-o-prevzeti', 'protokol' => 'Servisni-protokol', 'stitek' => 'Stitek'];

        return route('tisk.zakazka.' . $co, array_merge(
            ['zakazka' => $this, 'soubor' => ($nazvy[$co] ?? $co) . '-' . $this->cislo . '.pdf'],
            $query,
        ));
    }

    public function jeDobirka(): bool
    {
        return (bool) $this->dobirka && $this->zpusob_vydani === 'odeslani';
    }

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

        // Hotovo / Vydáno = zakázka je vyřízená: doplň datum vyřízení (od něj se počítá záruka a datum příjmu)
        static::saving(function (Zakazka $z) {
            if (in_array($z->stav, self::STAVY_HOTOVO, true) && ! $z->datum_vyrizeni) {
                $z->datum_vyrizeni = now()->toDateString();
            }
        });

        static::saved(function (Zakazka $z) {
            $z->synchronizujZalohu();
            $z->synchronizujPostovne();
            $z->synchronizujPrijem();
        });
        static::deleted(function (Zakazka $z) {
            PenezniDenik::where('zdroj_id', $z->id)
                ->whereIn('zdroj', ['zakazka', 'zakazka_zaloha', 'zakazka_postovne'])->delete();
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

    /** Poštovné, které za odeslání zaplatil servis = výdaj v peněžním deníku (zákazníkovi se účtuje zvlášť přes cenu dopravy). */
    public function synchronizujPostovne(): void
    {
        $klic = ['zdroj' => 'zakazka_postovne', 'zdroj_id' => $this->id];

        if ((float) $this->postovne_naklad > 0) {
            PenezniDenik::updateOrCreate($klic, [
                'datum' => ($this->odeslano_datum ?? $this->datum_vyrizeni ?? now())->toDateString(),
                'typ' => 'vydej',
                'popis' => 'Poštovné ' . $this->cislo . ($this->dopravce ? ' – ' . $this->dopravce : ''),
                'castka' => (float) $this->postovne_naklad,
                'kategorie' => 'Poštovné',
                'zpusob' => 'hotove',
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
        $castka = $this->cenaPoSleve() + (float) $this->cena_dopravy
            - ($zalohaZapoctena ? (float) $this->zaloha : 0);

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

    /** Další zařízení v téže zakázce (hlavní je `zarizeni`). */
    public function dalsiZarizeni(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Zarizeni::class, 'zakazka_zarizeni', 'zakazka_id', 'zarizeni_id')
            ->withPivot('zavada', 'pozadovane', 'prace')->orderBy('zakazka_zarizeni.id');
    }

    /** Názvy všech zařízení zakázky v jednom řádku (pro přehledy, kalendář, faktury, zprávy). */
    public function zarizeniText(): string
    {
        return $this->vsechnaZarizeni()->pluck('oznaceni')->filter()->implode(', ');
    }

    /** Je v zakázce víc než jedno zařízení? (závady se pak vypisují u zařízení) */
    public function maVicZarizeni(): bool
    {
        return $this->dalsiZarizeni->isNotEmpty();
    }

    /** Hlavní + další zařízení zakázky. */
    public function vsechnaZarizeni(): \Illuminate\Support\Collection
    {
        $hlavni = $this->zarizeni;
        // Závada a práce hlavního zařízení jsou ve společných polích zakázky (popis_zavady, navrh_reseni_prace);
        // u více zařízení se vypisují přímo u zařízení, u jednoho zůstávají v samostatných boxech dokladu.
        $vice = $this->dalsiZarizeni->isNotEmpty();
        $hlavni?->setAttribute('_zavada', $vice ? $this->popis_zavady : null);
        $hlavni?->setAttribute('_pozadovane', $vice ? $this->pozadovane_reseni : null);
        $hlavni?->setAttribute('_prace', $vice ? $this->navrh_reseni_prace : null);

        $dalsi = $this->dalsiZarizeni->each(function ($zar) {
            $zar->setAttribute('_zavada', $zar->pivot->zavada);
            $zar->setAttribute('_pozadovane', $zar->pivot->pozadovane);
            $zar->setAttribute('_prace', $zar->pivot->prace);
        });

        return collect([$hlavni])->merge($dalsi)->filter()->unique('id')->values();
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

    /** Položky nákupů koupené pro tuto zakázku (informační přehled nákladů). */
    public function nakupPolozky(): HasMany
    {
        return $this->hasMany(NakupPolozka::class, 'zakazka_id');
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
