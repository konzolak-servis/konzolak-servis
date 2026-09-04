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

    /** Volby pro Select seskupené podle kategorie: [kategorie => [nazev => nazev]]. */
    public static function volby(): array
    {
        return static::where('aktivni', true)
            ->orderBy('poradi')->orderBy('nazev')
            ->get()
            ->groupBy(fn ($m) => Zarizeni::KATEGORIE[$m->kategorie] ?? $m->kategorie)
            ->map(fn ($sk) => $sk->mapWithKeys(fn ($m) => [$m->nazev => $m->nazev])->all())
            ->all();
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
