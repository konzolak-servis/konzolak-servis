<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Models\Zakazka;
use App\Support\QrPlatba;
use Illuminate\View\View;

class VerejnyController extends Controller
{
    /** Veřejná stránka stavu zakázky (krátká URL /z/…, cíl QR z dokladu i webového formuláře). */
    public function stavZakazky(Zakazka $zakazka, string $token): View
    {
        abort_unless(hash_equals(QrPlatba::token('stav', $zakazka->id), $token), 404);

        $zakazka->load('zarizeni');

        $odeslani = $zakazka->zpusob_vydani === 'odeslani';
        $jizOdeslano = $odeslani && $zakazka->odeslano_datum;

        [$krok, $nadpis, $popis, $tonalita] = match (true) {
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

        return view('web.kontrola-stav', [
            'firma' => Firma::get(),
            'z' => $zakazka,
            'krok' => $krok,
            'nadpis' => $nadpis,
            'popis' => $popis,
            'tonalita' => $tonalita,
            'kUhrade' => $zakazka->doplatek(),
        ]);
    }
}
