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

    /** Typ podle názvu zařízení: název typu nebo některé z klíčových slov je obsaženo v názvu (bez diakritiky). */
    public static function najdiPodleNazvu(?string $nazev): ?self
    {
        $n = Str::lower(Str::ascii((string) $nazev));
        if ($n === '') {
            return null;
        }

        return static::orderBy('poradi')->get()->first(function (self $t) use ($n) {
            $slova = collect(explode(',', (string) $t->klicova_slova))->push($t->nazev)
                ->map(fn ($s) => Str::lower(Str::ascii(trim($s))))->filter();

            return $slova->contains(fn ($s) => str_contains($n, $s));
        });
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
