<?php

namespace App\Filament\Pages;

use App\Models\BazarNaklad;
use App\Models\Obchod;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Interní přehled bazaru – jen nákup vs. prodej a zisk.
 * ZÁMĚRNĚ oddělené od skupiny „Finance" / oficiálních účetních dat.
 */
class BazarPrehled extends Page
{
    protected string $view = 'filament.pages.bazar-prehled';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Sklad';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Bazar – přehled';

    protected static ?string $title = 'Bazar – přehled (interní)';

    public int $rok = 0;

    public function mount(): void
    {
        $this->rok = (int) now()->year;
    }

    public function getRokyProperty(): array
    {
        $min = (int) (Obchod::min(DB::raw('YEAR(datum)')) ?: now()->year);

        return range((int) now()->year, min($min, (int) now()->year));
    }

    /** Souhrnná čísla za rok. */
    public function getSouhrnProperty(): array
    {
        $rok = $this->rok;

        $nakoupeno = (float) Obchod::whereYear('datum', $rok)->sum('cena');
        $pocetNakup = Obchod::whereYear('datum', $rok)->count();

        $dilyIds = Obchod::whereYear('datum', $rok)->pluck('id');
        $dily = (float) BazarNaklad::whereIn('obchod_id', $dilyIds)->get()
            ->sum(fn (BazarNaklad $n) => $n->cena_celkem);

        $prodano = Obchod::where('prodano', true)->whereYear('prodej_datum', $rok)->get();
        $trzba = (float) $prodano->sum(fn (Obchod $o) => (float) $o->prodejni_cena);
        $zisk = (float) $prodano->sum(fn (Obchod $o) => (float) ($o->zisk ?? 0));

        $skladem = Obchod::where('prodano', false)->count();

        return [
            'nakoupeno' => $nakoupeno,
            'pocet_nakup' => $pocetNakup,
            'dily' => $dily,
            'investovano' => $nakoupeno + $dily,
            'trzba' => $trzba,
            'pocet_prodej' => $prodano->count(),
            'zisk' => $zisk,
            'skladem' => $skladem,
        ];
    }

    /** Nákup vs. prodej po měsících. */
    public function getMesiceProperty(): array
    {
        $rok = $this->rok;
        Carbon::setLocale('cs');
        $out = [];

        for ($m = 1; $m <= 12; $m++) {
            $nakup = (float) Obchod::whereYear('datum', $rok)->whereMonth('datum', $m)->sum('cena');
            $prodej = (float) Obchod::where('prodano', true)
                ->whereYear('prodej_datum', $rok)->whereMonth('prodej_datum', $m)->sum('prodejni_cena');

            $out[] = [
                'nazev' => ucfirst(Carbon::create($rok, $m, 1)->translatedFormat('M')),
                'nakup' => $nakup,
                'prodej' => $prodej,
            ];
        }

        $max = collect($out)->flatMap(fn ($r) => [$r['nakup'], $r['prodej']])->max() ?: 1;

        return ['radky' => $out, 'max' => $max];
    }

    /** Prodané kusy s rozpadem zisku. */
    public function getProdejeProperty(): array
    {
        return Obchod::where('prodano', true)
            ->whereYear('prodej_datum', $this->rok)
            ->orderByDesc('prodej_datum')
            ->get()
            ->map(fn (Obchod $o) => [
                'datum' => optional($o->prodej_datum)->format('d.m.Y'),
                'nazev' => $o->nazev,
                'nakup' => (float) $o->cena,
                'dily' => $o->naklady_dilu,
                'prodej' => (float) $o->prodejni_cena,
                'zisk' => (float) ($o->zisk ?? 0),
            ])
            ->all();
    }
}
