<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KatalogZarizeni extends Model
{
    protected $table = 'katalog_zarizeni';

    protected $guarded = [];

    protected $casts = [
        'aktivni' => 'boolean',
        'poradi' => 'integer',
    ];

    public function getPlnyNazevAttribute(): string
    {
        return trim($this->nazev . ($this->model_kod ? '  (' . $this->model_kod . ')' : ''));
    }

    /**
     * Volby pro Select seskupené podle kategorie: [kategorie => [nazev => nazev]].
     * Řazení: poradi (pořadí katalogu) | caste (nejčastěji přijímaná zařízení) | abeceda.
     */
    public static function volby(string $razeni = 'poradi'): array
    {
        $modely = static::where('aktivni', true)->orderBy('poradi')->orderBy('nazev')->get();
        $abc = fn ($t) => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $t));

        $pocty = [];
        if ($razeni === 'caste') {
            $oznaceni = \Illuminate\Support\Facades\DB::table('zarizeni')->pluck('oznaceni');
            foreach ($modely as $m) {
                $pocty[$m->id] = $oznaceni->filter(fn ($o) => str_starts_with((string) $o, $m->nazev))->count();
            }
        }

        $skupiny = $modely->groupBy(fn ($m) => Zarizeni::KATEGORIE[$m->kategorie] ?? $m->kategorie);

        if ($razeni === 'caste') {
            $skupiny = $skupiny->map(fn ($sk) => $sk->sortByDesc(fn ($m) => $pocty[$m->id] ?? 0))
                ->sortByDesc(fn ($sk) => $sk->sum(fn ($m) => $pocty[$m->id] ?? 0));
        } elseif ($razeni === 'abeceda') {
            $skupiny = $skupiny->map(fn ($sk) => $sk->sortBy(fn ($m) => $abc($m->nazev)))
                ->sortBy(fn ($sk, $nazev) => $abc($nazev));
        }

        return $skupiny
            ->map(fn ($sk) => $sk->mapWithKeys(fn ($m) => [$m->nazev => $m->nazev])->all())
            ->all();
    }

    /** Hledání v katalogu: shoduje se název modelu i název skupiny (např. „ovladač" najde všechny ovladače). */
    public static function hledej(?string $dotaz, string $razeni = 'poradi'): array
    {
        $q = mb_strtolower(trim((string) $dotaz));
        $out = [];
        foreach (static::volby($razeni) as $skupina => $modely) {
            $skupinaShoda = $q === '' || str_contains(mb_strtolower($skupina), $q);
            $nalezene = array_filter($modely, fn ($n) => $skupinaShoda || str_contains(mb_strtolower($n), $q));
            if ($nalezene) {
                $out[$skupina] = $nalezene;
            }
        }

        return $out;
    }

    /** Odhad kategorie zařízení podle názvu modelu. */
    public static function kategorieZNazvu(string $nazev): string
    {
        return match (true) {
            (bool) preg_match('/dualsense|dualshock|joy-?con|pro controller|elite|ovlada/iu', $nazev) => 'ovladac',
            (bool) preg_match('/playstation 5|\bPS5\b/iu', $nazev) => 'ps5',
            (bool) preg_match('/playstation 4|\bPS4\b/iu', $nazev) => 'ps4',
            (bool) preg_match('/playstation 3|\bPS3\b/iu', $nazev) => 'ps3',
            (bool) preg_match('/playstation 2|\bPS2\b/iu', $nazev) => 'ps2',
            (bool) preg_match('/xbox series|series [sx]\b/iu', $nazev) => 'xbox_series',
            (bool) preg_match('/xbox one/iu', $nazev) => 'xbox_one',
            (bool) preg_match('/xbox 360/iu', $nazev) => 'xbox_360',
            (bool) preg_match('/xbox/iu', $nazev) => 'xbox_series',
            (bool) preg_match('/switch/iu', $nazev) => 'switch',
            (bool) preg_match('/wii/iu', $nazev) => 'wii',
            (bool) preg_match('/3ds|dsi|nintendo ds/iu', $nazev) => '3ds',
            (bool) preg_match('/notebook|laptop/iu', $nazev) => 'notebook',
            (bool) preg_match('/\bPC\b|monitor/iu', $nazev) => 'pc',
            default => 'jine',
        };
    }
}
