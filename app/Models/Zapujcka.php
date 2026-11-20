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
        'vikend_sazba' => 'decimal:2',
        'vikend_cely' => 'decimal:2',
        'poplatek' => 'decimal:2',
        'srazky' => 'array',
        'fotky' => 'array',
        'fotky_vraceni' => 'array',
    ];

    /** Druhy srážek z kauce při vrácení. */
    public const DRUHY_SRAZEK = [
        'poskozeni' => 'Poškození zařízení',
        'prislusenstvi' => 'Chybějící / poškozené příslušenství',
        'cisteni' => 'Čištění',
        'jine' => 'Jiné',
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

    /** Poplatek za zapůjčení podle tarifu (0 při zápůjčce zdarma po dobu opravy). */
    public function vypocetPoplatku(?string $do = null): float
    {
        return $this->zdarma ? 0.0 : round(collect($this->rozpisPoplatku($do))->sum('castka'), 2);
    }

    public function maVikendovyTarif(): bool
    {
        return (float) $this->vikend_sazba > 0 || (float) $this->vikend_cely > 0;
    }

    /** Krátký popis ceníku do dokladů, např. „Po–Čt 350 Kč/den · Pá–Ne 400 Kč/den · víkend Pá–Ne 1 000 Kč". */
    public function tarifText(): string
    {
        $kc = fn ($v) => number_format((float) $v, 0, ',', ' ');
        if (! $this->maVikendovyTarif()) {
            return $kc($this->denni_sazba) . ' Kč / den';
        }

        if ((float) $this->vikend_sazba <= 0 || (float) $this->vikend_sazba === (float) $this->denni_sazba) {
            return collect([
                (float) $this->denni_sazba > 0 ? $kc($this->denni_sazba) . ' Kč/den' : null,
                (float) $this->vikend_cely > 0 ? 'celý víkend Pá–Ne ' . $kc($this->vikend_cely) . ' Kč' : null,
            ])->filter()->implode(' · ');
        }

        return collect([
            (float) $this->denni_sazba > 0 ? 'Po–Čt ' . $kc($this->denni_sazba) . ' Kč/den' : null,
            (float) $this->vikend_sazba > 0 ? 'Pá–Ne ' . $kc($this->vikend_sazba) . ' Kč/den' : null,
            (float) $this->vikend_cely > 0 ? 'celý víkend Pá–Ne ' . $kc($this->vikend_cely) . ' Kč' : null,
        ])->filter()->implode(' · ');
    }

    /**
     * Rozpis poplatku po dnech: účtuje se každý den zápůjčky (od data zapůjčení, počet = pocetDni).
     * Po–Čt = denní sazba, Pá–Ne = víkendová denní sazba, celý víkend Pá+So+Ne = paušál (když je levnější).
     *
     * @return array<int, array{popis: string, pocet: int, sazba: float, castka: float}>
     */
    public function rozpisPoplatku(?string $do = null): array
    {
        $dni = $this->pocetDni($do);
        $od = ($this->datum_zapujceni ?? now())->copy()->startOfDay();
        $vsedni = (float) $this->denni_sazba;
        $vikendDen = (float) $this->vikend_sazba > 0 ? (float) $this->vikend_sazba : $vsedni;
        $vikendCely = (float) $this->vikend_cely;

        [$nVsedni, $nVikend, $nCely] = [0, 0, 0];
        for ($i = 0; $i < $dni; $i++) {
            $den = $od->copy()->addDays($i);
            $dow = $den->dayOfWeekIso; // 1 = Po … 7 = Ne
            if ($dow === 5 && $i + 2 < $dni && $vikendCely > 0 && $vikendCely < 3 * $vikendDen) {
                $nCely++;
                $i += 2;     // pátek + sobota + neděle jako balíček

                continue;
            }
            $dow >= 5 ? $nVikend++ : $nVsedni++;
        }

        $radky = [];
        if (! $this->maVikendovyTarif()) {
            return [['popis' => $dni . ' ' . self::dnu($dni), 'pocet' => $dni, 'sazba' => $vsedni, 'castka' => $dni * $vsedni]];
        }
        if ($vikendDen === $vsedni && ($nVsedni + $nVikend)) {
            // jedna cena za den (Po–Ne) – rozlišuje se jen celý víkend
            $n = $nVsedni + $nVikend;
            $radky[] = ['popis' => $n . '× den', 'pocet' => $n, 'sazba' => $vsedni, 'castka' => $n * $vsedni];
            [$nVsedni, $nVikend] = [0, 0];
        }
        if ($nVsedni) {
            $radky[] = ['popis' => $nVsedni . '× Po–Čt', 'pocet' => $nVsedni, 'sazba' => $vsedni, 'castka' => $nVsedni * $vsedni];
        }
        if ($nVikend) {
            $radky[] = ['popis' => $nVikend . '× den Pá–Ne', 'pocet' => $nVikend, 'sazba' => $vikendDen, 'castka' => $nVikend * $vikendDen];
        }
        if ($nCely) {
            $radky[] = ['popis' => $nCely . '× celý víkend', 'pocet' => $nCely, 'sazba' => $vikendCely, 'castka' => $nCely * $vikendCely];
        }

        return $radky;
    }

    public static function dnu(int $n): string
    {
        return $n == 1 ? 'den' : ($n < 5 ? 'dny' : 'dní');
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
    /** Počet dní prodlení po sjednaném termínu vrácení (0, když termín není nebo vráceno včas). */
    public function dnyProdleni(?string $do = null): int
    {
        if (! $this->vratit_do) {
            return 0;
        }
        $do = $do ? \Illuminate\Support\Carbon::parse($do) : ($this->datum_vraceni ?? now());

        return max(0, (int) $this->vratit_do->copy()->startOfDay()->diffInDays($do->copy()->startOfDay(), false));
    }

    /** Rozpis srážek z kauce (s popisky druhů). */
    public function rozpisSrazek(): array
    {
        return collect($this->srazky ?: [])
            ->filter(fn ($s) => (float) ($s['castka'] ?? 0) > 0)
            ->map(fn ($s) => [
                'druh' => self::DRUHY_SRAZEK[$s['druh'] ?? 'jine'] ?? 'Jiné',
                'popis' => $s['popis'] ?? null,
                'castka' => (float) $s['castka'],
            ])->values()->all();
    }

    /**
     * @param  array<int, array{druh: string, popis: ?string, castka: float}>|null  $srazky  rozpis srážek (má přednost před $srazka)
     */
    public function vratit(string $datum, float $srazka = 0, ?string $duvod = null, ?string $poznamka = null, array $stavyPolozek = [], ?float $poplatek = null, ?array $srazky = null, ?string $forma = null): void
    {
        if ($srazky !== null) {
            $srazky = array_values(array_filter($srazky, fn ($s) => (float) ($s['castka'] ?? 0) > 0));
            $srazka = (float) array_sum(array_map(fn ($s) => (float) $s['castka'], $srazky));
            $duvod = implode('; ', array_map(fn ($s) => (self::DRUHY_SRAZEK[$s['druh'] ?? 'jine'] ?? 'Jiné') . (filled($s['popis'] ?? null) ? ': ' . $s['popis'] : ''), $srazky)) ?: null;
        } elseif ($srazka > 0) {
            $srazky = [['druh' => 'jine', 'popis' => $duvod, 'castka' => $srazka]];
        }

        DB::transaction(function () use ($datum, $srazka, $duvod, $poznamka, $stavyPolozek, $poplatek, $srazky, $forma) {
            foreach ($this->polozky as $p) {
                if (array_key_exists($p->id, $stavyPolozek)) {
                    $p->update(['stav_pri_vraceni' => $stavyPolozek[$p->id]]);
                    // aktuální stav zařízení v seznamu zařízení k zápůjčce
                    if ($p->zapujcni_zarizeni_id && filled($stavyPolozek[$p->id])) {
                        ZapujcniZarizeni::whereKey($p->zapujcni_zarizeni_id)->update(['stav_popis' => $stavyPolozek[$p->id]]);
                    }
                }
            }

            // srážka může přesáhnout kauci (škoda) – rozdíl je pak doplatek vypůjčitele
            $srazka = max(0, $srazka);
            $poplatek = max(0, $poplatek ?? $this->vypocetPoplatku($datum));
            $this->update([
                'stav' => 'vraceno',
                'datum_vraceni' => $datum,
                'pocet_dni' => $this->pocetDni($datum),
                'poplatek' => $poplatek,
                'kauce_srazka' => $srazka,
                'srazka_duvod' => $srazka > 0 ? $duvod : null,
                'srazky' => $srazky ?: null,
                'vyrovnani_forma' => $forma,
                'poznamka_vraceni' => $poznamka,
            ]);

            PenezniDenik::where('zdroj', 'zapujcka')->where('zdroj_id', $this->id)->delete();
            if ($srazka > 0) {
                PenezniDenik::create([
                    'datum' => $datum,
                    'typ' => 'prijem',
                    'popis' => 'Zápůjčka ' . $this->cislo . ' – náhrada škody / srážka' . ($duvod ? ' (' . \Illuminate\Support\Str::limit($duvod, 150) . ')' : ''),
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
            $this->update(['stav' => 'zapujceno', 'datum_vraceni' => null, 'kauce_srazka' => 0, 'srazka_duvod' => null, 'srazky' => null, 'vyrovnani_forma' => null, 'poznamka_vraceni' => null, 'pocet_dni' => null, 'poplatek' => 0]);
        });
    }

    public function tiskUrl(string $co): string
    {
        $nazvy = ['protokol' => 'Predavaci-protokol', 'kauce' => 'Prijem-kauce', 'vraceni' => 'Vraceni-zapujcky', 'vyuctovani' => 'Vyuctovani-kauce', 'sada-predani' => 'Tisk-predani', 'sada-vraceni' => 'Tisk-vraceni'];

        return route('tisk.zapujcka.' . $co, ['zapujcka' => $this, 'soubor' => ($nazvy[$co] ?? $co) . '-' . $this->cislo . '.pdf']);
    }
}
