<?php

namespace App\Support;

use App\Models\Zakazka;
use Illuminate\Support\Str;

/** Společná logika veřejného zjišťování stavu zakázky (web, QR stránka i chatbot). */
class StavZakazky
{
    /** [krok, nadpis, popis, tonalita] – texty pro zákazníka podle stavu zakázky. */
    public static function popis(Zakazka $zakazka): array
    {
        $odeslani = $zakazka->zpusob_vydani === 'odeslani';
        $jizOdeslano = $odeslani && $zakazka->odeslano_datum;

        return match (true) {
            $zakazka->stav === 'prijato' => [0, 'Přijato do servisu', 'Zařízení máme převzaté, brzy se do něj podíváme.', 'info'],
            $zakazka->stav === 'diagnostika' => [1, 'Probíhá diagnostika', 'Zjišťujeme závadu a rozsah opravy.', 'info'],
            $zakazka->stav === 'ceka_na_dil' => [2, 'Čeká na náhradní díl', 'Máme objednaný díl, po dodání pokračujeme v opravě.', 'wait'],
            $zakazka->stav === 'hotovo' && $jizOdeslano => [4, 'Odesláno zpět', 'Zařízení je na cestě k vám – sledovací číslo najdete níže.', 'ok'],
            $zakazka->stav === 'hotovo' && $odeslani => [3, 'Hotovo – připravujeme k odeslání', 'Oprava je dokončená, brzy zařízení odešleme zpět.', 'ok'],
            $zakazka->stav === 'hotovo' => [3, 'Hotovo – připraveno k vyzvednutí', 'Oprava je dokončená, zařízení si můžete vyzvednout.', 'ok'],
            $zakazka->stav === 'vydano' && $odeslani => [4, 'Odesláno', 'Zakázka byla uzavřena a zařízení odesláno zpět.', 'done'],
            $zakazka->stav === 'vydano' => [4, 'Vyzvednuto', 'Zakázka byla uzavřena a zařízení předáno.', 'done'],
            $zakazka->stav === 'nerentabilni' => [1, 'Oprava nerentabilní', 'Oprava se nevyplatí. Ozveme se s dalším postupem.', 'stop'],
            $zakazka->stav === 'storno' => [0, 'Zakázka stornována', 'Zakázka byla zrušena.', 'stop'],
            default => [0, 'Přijato do servisu', '', 'info'],
        };
    }

    /**
     * Najde zakázku podle čísla a ověřovacího údaje (příjmení, e-mail nebo telefon zákazníka).
     * Vrací null, pokud zakázka neexistuje nebo údaj nesedí.
     */
    public static function najdi(string $cislo, string $overeni): ?Zakazka
    {
        $cislo = strtoupper(trim($cislo));
        if (! Str::startsWith($cislo, 'SL-') && preg_match('/^\d{4}-\d+$/', $cislo)) {
            $cislo = 'SL-' . $cislo;
        }

        $norm = fn (?string $s) => Str::lower(Str::ascii((string) $s));
        $vstup = $norm(trim($overeni));
        $vstupTel = preg_replace('/\D+/', '', $overeni);

        $zakazka = Zakazka::query()->where('cislo', $cislo)->with(['zakaznik', 'zarizeni', 'dalsiZarizeni'])->first();
        $k = $zakazka?->zakaznik;

        $sedi = $k && (
            ($vstup !== '' && str_contains($norm($k->jmeno), $vstup))
            || ($vstup !== '' && str_contains($norm($k->firma_nazev), $vstup))
            || (filled($k->email) && $norm($k->email) === $vstup)
            || (strlen($vstupTel) >= 6 && filled($k->telefon)
                && str_contains(preg_replace('/\D+/', '', $k->telefon), $vstupTel))
        );

        return $sedi ? $zakazka : null;
    }
}
