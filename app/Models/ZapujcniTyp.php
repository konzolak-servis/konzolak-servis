<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Typ zápůjčního zařízení = šablona ceníku a výbavy pro fyzická zařízení na zápůjčním skladu. */
class ZapujcniTyp extends Model
{
    protected $table = 'zapujcni_typy';

    protected $guarded = [];

    protected $casts = [
        'denni_sazba' => 'decimal:2',
        'vikend_cely' => 'decimal:2',
        'kauce' => 'decimal:2',
        'hodnota' => 'decimal:2',
    ];

    /** Pole, která se z typu předvyplní do zařízení. */
    public const POLE = ['denni_sazba', 'vikend_cely', 'kauce', 'hodnota', 'prislusenstvi'];

    public function zarizeni(): HasMany
    {
        return $this->hasMany(ZapujcniZarizeni::class, 'typ_id');
    }

    /**
     * Typ podle názvu zařízení (název typu nebo klíčové slovo, např. modelové číslo „cfi-1", obsažené v názvu).
     * Vyhrává nejdelší (nejkonkrétnější) shoda – „cfi-zct" (ovladač) před „cfi-". Když název nic neříká,
     * zkusí se platforma z bazaru (např. „PlayStation 5").
     */
    public static function najdiPodleNazvu(?string $nazev, ?string $platforma = null): ?self
    {
        $typy = static::orderBy('poradi')->get();

        foreach ([$nazev, $platforma] as $text) {
            $n = Str::lower(Str::ascii((string) $text));
            if ($n === '') {
                continue;
            }

            $nejlepsi = null;
            $delka = 0;
            foreach ($typy as $t) {
                $slova = collect(explode(',', (string) $t->klicova_slova))->push($t->nazev)
                    ->map(fn ($s) => Str::lower(Str::ascii(trim($s))))->filter();
                foreach ($slova as $s) {
                    if (str_contains($n, $s) && mb_strlen($s) > $delka) {
                        [$nejlepsi, $delka] = [$t, mb_strlen($s)];
                    }
                }
            }
            if ($nejlepsi) {
                return $nejlepsi;
            }
        }

        return null;
    }

    public function tarifText(): string
    {
        return (new Zapujcka(['denni_sazba' => $this->denni_sazba, 'vikend_cely' => $this->vikend_cely]))->tarifText();
    }

    /** Přenese aktuální ceník typu na všechna jeho zařízení (přepíše ceny a kauci). */
    public function pouzitNaZarizeni(): int
    {
        return $this->zarizeni()->update([
            'denni_sazba' => $this->denni_sazba,
            'vikend_sazba' => null,
            'vikend_cely' => $this->vikend_cely,
            'kauce' => $this->kauce,
        ]);
    }
}
