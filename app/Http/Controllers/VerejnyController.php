<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Models\Zakazka;
use App\Support\QrPlatba;
use Illuminate\View\View;

class VerejnyController extends Controller
{
    /** Veřejná stránka stavu zakázky – přístup přes token v URL (z QR na dokladu). */
    public function stavZakazky(Zakazka $zakazka, string $token): View
    {
        abort_unless(hash_equals(QrPlatba::token('stav', $zakazka->id), $token), 404);

        $zakazka->load('zarizeni');
        $firma = Firma::get();

        // mapa stavů na krok v časové ose (0–4) a přátelský text
        [$krok, $nadpis, $popis, $tonalita] = match ($zakazka->stav) {
            'prijato' => [0, 'Přijato do servisu', 'Zařízení máme převzaté, brzy se do něj podíváme.', 'info'],
            'diagnostika' => [1, 'Probíhá diagnostika', 'Zjišťujeme závadu a rozsah opravy.', 'info'],
            'ceka_na_dil' => [2, 'Čeká na náhradní díl', 'Máme objednaný díl, po dodání pokračujeme v opravě.', 'wait'],
            'hotovo' => [3, 'Hotovo – připraveno k vyzvednutí', 'Oprava je dokončená, zařízení si můžete vyzvednout.', 'ok'],
            'vydano' => [4, 'Vyzvednuto', 'Zakázka byla uzavřena a zařízení předáno.', 'done'],
            'nerentabilni' => [1, 'Oprava nerentabilní', 'Oprava se nevyplatí. Ozveme se s dalším postupem.', 'stop'],
            'storno' => [0, 'Zakázka stornována', 'Zakázka byla zrušena.', 'stop'],
            default => [0, 'Přijato do servisu', '', 'info'],
        };

        $kUhrade = max((float) $zakazka->cena_celkem - (float) $zakazka->zaloha, 0);

        return view('verejne.stav-zakazky', [
            'firma' => $firma,
            'z' => $zakazka,
            'krok' => $krok,
            'nadpis' => $nadpis,
            'popis' => $popis,
            'tonalita' => $tonalita,
            'kUhrade' => $kUhrade,
            'adminUrl' => route('filament.admin.resources.zakazkas.edit', $zakazka),
        ]);
    }
}
