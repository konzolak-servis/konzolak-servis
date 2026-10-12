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

        [$krok, $nadpis, $popis, $tonalita] = \App\Support\StavZakazky::popis($zakazka);

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
