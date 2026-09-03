<?php

namespace App\Models;

use App\Support\Cisla;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjednavkaDilu extends Model
{
    protected $table = 'objednavky_dilu';

    protected $guarded = [];

    protected $casts = [
        'datum_objednavky' => 'date',
        'ocekavane_doruceni' => 'date',
        'doruceno_datum' => 'date',
        'mnozstvi' => 'decimal:3',
        'cena_odhad' => 'decimal:2',
    ];

    public const STAVY = [
        'objednano' => 'Objednáno',
        'dorazilo' => 'Dorazilo',
        'zruseno' => 'Zrušeno',
    ];

    protected static function booted(): void
    {
        static::creating(function (ObjednavkaDilu $o) {
            $o->cislo ??= Cisla::dalsi('objdil', 'OBJ');
            $o->datum_objednavky ??= now()->toDateString();
        });

        static::saved(function (ObjednavkaDilu $o) {
            // Když díl dorazil a zakázka čekala na díl, vrať ji do práce (diagnostika)
            // a poznač si to – ať v zakázce nesvítí „čeká na díl".
            if ($o->wasChanged('stav') && $o->stav === 'dorazilo'
                && $o->zakazka && $o->zakazka->stav === 'ceka_na_dil') {
                $o->zakazka->update([
                    'stav' => 'diagnostika',
                    'dil_objednany' => true,
                    'dil_info' => trim(($o->zakazka->dil_info ? $o->zakazka->dil_info . "\n" : '')
                        . 'Díl „' . $o->nazev_dilu . '" doručen ' . now()->format('d.m.Y') . '.'),
                ]);
            }
        });
    }

    public function zakazka(): BelongsTo
    {
        return $this->belongsTo(Zakazka::class, 'zakazka_id');
    }
}
