<?php

namespace App\Models;

use App\Support\Cisla;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Zápůjčka zařízení zákazníkovi (např. náhradní konzole / ovladač po dobu opravy) proti vratné kauci.
 * Kauce není příjem – do peněžního deníku jde jen část, která se při vrácení nevrátí (srážka).
 */
class Zapujcka extends Model
{
    protected $table = 'zapujcky';

    protected $guarded = [];

    protected $casts = [
        'datum_zapujceni' => 'date',
        'vratit_do' => 'date',
        'datum_vraceni' => 'date',
        'kauce' => 'decimal:2',
        'kauce_srazka' => 'decimal:2',
        'zdarma' => 'boolean',
        'denni_sazba' => 'decimal:2',
        'poplatek' => 'decimal:2',
    ];

    public const STAVY = [
        'zapujceno' => 'Zapůjčeno',
        'vraceno' => 'Vráceno',
        'storno' => 'Storno',
    ];

    protected static function booted(): void
    {
        static::creating(function (Zapujcka $z) {
            $z->cislo ??= Cisla::dalsi('zapujcka', 'ZAP');
            $z->datum_zapujceni ??= now()->toDateString();
            $z->stav ??= 'zapujceno';
        });
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
        return $this->hasMany(ZapujckaPolozka::class, 'zapujcka_id')->orderBy('poradi')->orderBy('id');
    }

    /** Počet účtovaných dní (den zapůjčení se počítá, minimálně 1). */
    public function pocetDni(?string $do = null): int
    {
        $od = $this->datum_zapujceni ?? now();
        $do = $do ? \Illuminate\Support\Carbon::parse($do) : ($this->datum_vraceni ?? now());

        return max(1, (int) $od->copy()->startOfDay()->diffInDays($do->copy()->startOfDay()));
    }

    /** Poplatek za zapůjčení: dny × denní sazba (0 při zápůjčce zdarma po dobu opravy). */
    public function vypocetPoplatku(?string $do = null): float
    {
        return $this->zdarma ? 0.0 : round($this->pocetDni($do) * (float) $this->denni_sazba, 2);
    }

    /** Kauce vrácená zákazníkovi = kauce − srážka − poplatek za zapůjčení. */
    public function vracenaKauce(): float
    {
        return max(0, (float) $this->kauce - (float) $this->kauce_srazka - (float) $this->poplatek);
    }

    /** Co zákazník doplácí, když poplatek + srážka převýší kauci. */
    public function doplatek(): float
    {
        return max(0, (float) $this->poplatek + (float) $this->kauce_srazka - (float) $this->kauce);
    }

    public function jePoTerminu(): bool
    {
        return $this->stav === 'zapujceno' && $this->vratit_do && $this->vratit_do->isPast() && ! $this->vratit_do->isToday();
    }

    /**
     * Vrácení zařízení: stav položek, datum, srážka z kauce. Sražená část se zapíše jako příjem do deníku.
     *
     * @param  array<int, string|null>  $stavyPolozek  [polozka_id => stav při vrácení]
     */
    public function vratit(string $datum, float $srazka = 0, ?string $duvod = null, ?string $poznamka = null, array $stavyPolozek = [], ?float $poplatek = null): void
    {
        DB::transaction(function () use ($datum, $srazka, $duvod, $poznamka, $stavyPolozek, $poplatek) {
            foreach ($this->polozky as $p) {
                if (array_key_exists($p->id, $stavyPolozek)) {
                    $p->update(['stav_pri_vraceni' => $stavyPolozek[$p->id]]);
                    // aktuální stav zařízení v seznamu zařízení k zápůjčce
                    if ($p->zapujcni_zarizeni_id && filled($stavyPolozek[$p->id])) {
                        ZapujcniZarizeni::whereKey($p->zapujcni_zarizeni_id)->update(['stav_popis' => $stavyPolozek[$p->id]]);
                    }
                }
            }

            $srazka = min(max(0, $srazka), (float) $this->kauce);
            $poplatek = max(0, $poplatek ?? $this->vypocetPoplatku($datum));
            $this->update([
                'stav' => 'vraceno',
                'datum_vraceni' => $datum,
                'pocet_dni' => $this->pocetDni($datum),
                'poplatek' => $poplatek,
                'kauce_srazka' => $srazka,
                'srazka_duvod' => $srazka > 0 ? $duvod : null,
                'poznamka_vraceni' => $poznamka,
            ]);

            PenezniDenik::where('zdroj', 'zapujcka')->where('zdroj_id', $this->id)->delete();
            if ($srazka > 0) {
                PenezniDenik::create([
                    'datum' => $datum,
                    'typ' => 'prijem',
                    'popis' => 'Zápůjčka ' . $this->cislo . ' – sražená kauce' . ($duvod ? ' (' . $duvod . ')' : ''),
                    'castka' => $srazka,
                    'kategorie' => 'Služby',
                    'kde' => $this->zakaznik?->nazev,
                    'zdroj' => 'zapujcka',
                    'zdroj_id' => $this->id,
                ]);
            }
            if ($poplatek > 0) {
                PenezniDenik::create([
                    'datum' => $datum,
                    'typ' => 'prijem',
                    'popis' => 'Zápůjčka ' . $this->cislo . ' – zapůjčení ' . $this->pocet_dni . ' dní',
                    'castka' => $poplatek,
                    'kategorie' => 'Služby',
                    'kde' => $this->zakaznik?->nazev,
                    'zdroj' => 'zapujcka',
                    'zdroj_id' => $this->id,
                ]);
            }
        });
    }

    /** Zrušení vrácení (překlep) – zápůjčka znovu běží, srážka z deníku zmizí. */
    public function zrusitVraceni(): void
    {
        DB::transaction(function () {
            PenezniDenik::where('zdroj', 'zapujcka')->where('zdroj_id', $this->id)->delete();
            $this->polozky()->update(['stav_pri_vraceni' => null]);
            $this->update(['stav' => 'zapujceno', 'datum_vraceni' => null, 'kauce_srazka' => 0, 'srazka_duvod' => null, 'poznamka_vraceni' => null, 'pocet_dni' => null, 'poplatek' => 0]);
        });
    }

    public function tiskUrl(string $co): string
    {
        $nazvy = ['protokol' => 'Predavaci-protokol', 'kauce' => 'Prijem-kauce', 'vraceni' => 'Vraceni-zapujcky'];

        return route('tisk.zapujcka.' . $co, ['zapujcka' => $this, 'soubor' => ($nazvy[$co] ?? $co) . '-' . $this->cislo . '.pdf']);
    }
}
