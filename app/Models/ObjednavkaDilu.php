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
        'postovne' => 'decimal:2',
    ];

    public const STAVY = [
        'objednano' => 'Objednáno',
        'dorazilo' => 'Dorazilo',
        'zruseno' => 'Zrušeno',
    ];

    /** Stavy zakázky, které objednáním dílu přejdou na „čeká na díl". */
    public const STAVY_ZAKAZKY_PRED_DILEM = ['prijato', 'diagnostika'];

    protected static function booted(): void
    {
        static::creating(function (ObjednavkaDilu $o) {
            $o->cislo ??= Cisla::dalsi('objdil', 'OBJ');
            $o->datum_objednavky ??= now()->toDateString();
            $o->stav ??= 'objednano';
        });

        static::saved(function (ObjednavkaDilu $o) {
            // Doklad nahraný k už naskladněné objednávce → připoj ho i k jejímu nákupu.
            if ($o->wasChanged('doklad_soubor') && $o->doklad_soubor && $o->nakup_id) {
                Nakup::whereKey($o->nakup_id)->update(['doklad_soubor' => $o->doklad_soubor]);
            }

            // Zakázku načíst vždy čerstvě – načtená relace může být zastaralá
            // (např. objednávka uložená nejdřív bez zakázky a přiřazená až potom).
            $zakazka = $o->zakazka_id ? Zakazka::find($o->zakazka_id) : null;
            $o->setRelation('zakazka', $zakazka);

            // Objednaný díl k zakázce, která je teprve v práci → zakázka „čeká na díl".
            if ($o->stav === 'objednano' && $zakazka
                && ($o->wasRecentlyCreated || $o->wasChanged(['stav', 'zakazka_id']))
                && in_array($zakazka->stav, self::STAVY_ZAKAZKY_PRED_DILEM, true)) {
                $zakazka->update([
                    'stav' => 'ceka_na_dil',
                    'dil_objednany' => true,
                    'dil_info' => self::pripojInfo($zakazka->dil_info,
                        'Díl „' . $o->nazev_dilu . '" objednán ' . ($o->datum_objednavky ?? now())->format('d.m.Y')
                        . ($o->dodavatel ? ' (' . $o->dodavatel . ')' : '') . ', ' . $o->cislo . '.'),
                ]);
            }

            // Když díl dorazil a zakázka čekala na díl, vrať ji do práce (diagnostika)
            // a poznač si to – ať v zakázce nesvítí „čeká na díl". Jen pokud na ni
            // nečeká žádný další objednaný díl.
            if ($o->wasChanged('stav') && $o->stav === 'dorazilo'
                && $zakazka && $zakazka->stav === 'ceka_na_dil'
                && ! self::where('zakazka_id', $o->zakazka_id)->where('stav', 'objednano')->exists()) {
                $zakazka->update([
                    'stav' => 'diagnostika',
                    'dil_objednany' => true,
                    'dil_info' => self::pripojInfo($zakazka->dil_info,
                        'Díl „' . $o->nazev_dilu . '" doručen ' . now()->format('d.m.Y') . '.'),
                ]);
            }
        });
    }

    /** Připíše řádek k info o dílu zakázky; drží posledních 255 znaků (sloupec je string). */
    public static function pripojInfo(?string $puvodni, string $radek): string
    {
        $text = trim(($puvodni ? $puvodni . "\n" : '') . $radek);

        return mb_strlen($text) > 255 ? mb_substr($text, -255) : $text;
    }

    public function zakazka(): BelongsTo
    {
        return $this->belongsTo(Zakazka::class, 'zakazka_id');
    }
}
