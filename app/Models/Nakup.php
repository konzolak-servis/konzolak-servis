<?php

namespace App\Models;

use App\Support\Cisla;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Nakup extends Model
{
    protected $table = 'nakupy';

    protected $guarded = [];

    protected $casts = [
        'datum' => 'date',
        'celkem' => 'decimal:2',
        'postovne' => 'decimal:2',
        'datum_naskladneni' => 'date',
        'naskladneno' => 'boolean',
        'preneseno_do_objednavek' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Nakup $n) {
            $n->cislo ??= Cisla::dalsi('nakup', 'NAK');
            $n->datum ??= now()->toDateString();
        });
    }

    public function polozky(): HasMany
    {
        return $this->hasMany(NakupPolozka::class, 'nakup_id');
    }

    public function pohyby(): HasMany
    {
        return $this->hasMany(SkladovyPohyb::class, 'nakup_id');
    }

    /**
     * Vrátí naskladnění zpět, aby šel nákup upravit: odebere kusy ze skladu (a vrátí vážený
     * průměr do stavu před příjmem), smaže skladové pohyby i výdaj v peněžním deníku.
     * Skladové položky zůstanou (při novém naskladnění se použijí znovu).
     *
     * @return array<string> varování (např. kusy už částečně vydané – stav půjde do mínusu)
     */
    public function zrusitNaskladneni(): array
    {
        if (! $this->naskladneno) {
            return [];
        }

        $varovani = [];

        DB::transaction(function () use (&$varovani) {
            foreach ($this->pohyby()->where('typ', 'prijem')->with('skladPolozka')->get() as $pohyb) {
                $s = $pohyb->skladPolozka;
                if ($s) {
                    $q = (float) $s->mnozstvi_skladem;
                    $p = (float) $s->cena_ks_prumer;
                    $dq = (float) $pohyb->mnozstvi;
                    $nq = round($q - $dq, 3);
                    $np = $nq > 0 ? max(0, ($q * $p - $dq * (float) $pohyb->cena_ks) / $nq) : $p;

                    if ($nq < 0) {
                        $varovani[] = $s->nazev . ': už vydáno, stav bude ' . rtrim(rtrim(number_format($nq, 3, ',', ''), '0'), ',') . ' ks';
                    }

                    $s->update(['mnozstvi_skladem' => $nq, 'cena_ks_prumer' => round($np, 2)]);
                }
                $pohyb->delete();
            }

            PenezniDenik::where('zdroj', 'nakup')->where('zdroj_id', $this->id)->delete();

            $this->update(['naskladneno' => false, 'datum_naskladneni' => null]);
        });

        return $varovani;
    }

    /**
     * Naskladní všechny položky (příjem + přepočet váženého průměru) a zapíše výdaj
     * do peněžního deníku. Poštovné se rozpočítá do skladové ceny kusů poměrem cen položek
     * a připočte se k celkovému výdaji v deníku.
     */
    public function naskladnit(): void
    {
        if ($this->naskladneno) {
            return;
        }

        DB::transaction(function () {
            $datumNask = now()->toDateString();
            // spotřební materiál (pasta, tekutý kov…) se nenaskladňuje – je to náklad firmy
            $nenaskladnuje = fn ($p) => $p->neskladovat || (bool) $p->skladPolozka?->spotrebni;
            $vseNeskladovat = $this->polozky->isNotEmpty() && $this->polozky->every($nenaskladnuje);

            // Poštovné za balík se rozpočítá do položek poměrem jejich ceny (bez cen → rovným dílem),
            // takže skladová cena = skutečně zaplacená cena vč. dopravy.
            $postovne = (float) $this->postovne;
            $sumaPolozek = (float) $this->polozky->sum('castka_celkem');
            $pocet = max(1, $this->polozky->count());
            $podilPostovneho = fn ($p) => $postovne * ($sumaPolozek > 0 ? (float) $p->castka_celkem / $sumaPolozek : 1 / $pocet);

            foreach ($this->polozky as $p) {
                // Obecný provozní náklad (předplatné, kancelář, vybavení, spotřební materiál…) – nezakládá
                // ani nemění skladovou položku, jen se počítá do celkové částky níže.
                if ($nenaskladnuje($p)) {
                    continue;
                }

                $sklad = $p->skladPolozka ?? SkladPolozka::create([
                    'nazev' => $p->nazev,
                ]);

                // Pohyb na skladě nese datum NASKLADNĚNÍ (kdy díl dorazil); datum nákupu zůstává u nákupu a v deníku.
                $ks = (float) $p->mnozstvi_ks;
                $cenaKs = $ks > 0 ? ((float) $p->castka_celkem + $podilPostovneho($p)) / $ks : 0;

                $sklad->prijem($ks, $cenaKs, [
                    'zdroj' => 'nakup',
                    'nakup_id' => $this->id,
                    'datum' => $datumNask,
                    'poznamka' => $this->dodavatel,
                ]);

                if (! $p->sklad_polozka_id) {
                    $p->update(['sklad_polozka_id' => $sklad->id]);
                }
            }

            $polozkyCelkem = (float) $this->polozky()->sum('castka_celkem');
            $castka = $polozkyCelkem + (float) $this->postovne;

            PenezniDenik::create([
                'datum' => $this->datum?->toDateString() ?? now()->toDateString(),
                'typ' => 'vydej',
                'popis' => 'Nákup ' . $this->cislo . ($this->dodavatel ? ' – ' . $this->dodavatel : '')
                    . ($this->datum && $this->datum->toDateString() !== $datumNask ? ' (naskladněno ' . now()->format('d.m.') . ')' : '')
                    . ($this->postovne > 0 ? ' (vč. poštovného ' . number_format((float) $this->postovne, 0, ',', ' ') . ' Kč)' : ''),
                'castka' => $castka,
                'kategorie' => $vseNeskladovat ? 'Náklady firmy' : 'Materiál',
                'kde' => $this->dodavatel,
                'zdroj' => 'nakup',
                'zdroj_id' => $this->id,
            ]);

            $this->update(['naskladneno' => true, 'celkem' => $castka, 'datum_naskladneni' => $datumNask]);
        });
    }
}
