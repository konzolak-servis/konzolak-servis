<?php

namespace App\Http\Controllers;

use App\Models\Faktura;
use App\Models\Firma;
use App\Models\Nabidka;
use App\Models\Obchod;
use App\Models\Zakazka;
use App\Support\Tisk;
use Illuminate\Http\Response;

class TiskController extends Controller
{
    /** Veřejná URL stavu zakázky (do QR na dokladu / štítku / protokolu). */
    private function stavUrl(Zakazka $zakazka): string
    {
        return route('verejne.stav', [
            'zakazka' => $zakazka->id,
            'token' => \App\Support\QrPlatba::token('stav', $zakazka->id),
        ]);
    }

    public function servisniDoklad(Zakazka $zakazka): Response
    {
        $zakazka->load(['zakaznik', 'zarizeni', 'polozky']);

        return Tisk::pdf('pdf.servisni-doklad', [
            'firma' => Firma::get(),
            'z' => $zakazka,
            'qr' => \App\Support\Qr::dataUri($this->stavUrl($zakazka), 200),
        ], 'Doklad-o-prevzeti-' . $zakazka->cislo);
    }

    public function servisniProtokol(Zakazka $zakazka): Response
    {
        $zakazka->load(['zakaznik', 'zarizeni', 'polozky', 'reklamaceK']);

        $firma = Firma::get();
        $doplatek = max((float) $zakazka->cena_celkem - (float) $zakazka->zaloha, 0);

        return Tisk::pdf('pdf.servisni-protokol', [
            'firma' => $firma,
            'z' => $zakazka,
            'qr' => \App\Support\Qr::dataUri($this->stavUrl($zakazka), 180),
            'qrPlatba' => $zakazka->zpusob_uhrady === 'ucet' && $doplatek > 0
                ? \App\Support\QrPlatba::dataUri($firma->cislo_uctu, $doplatek,
                    preg_replace('/\D/', '', $zakazka->cislo), 'Oprava ' . $zakazka->cislo, 200)
                : '',
        ], 'Servisni-protokol-' . $zakazka->cislo);
    }

    public function stitek(Zakazka $zakazka): Response
    {
        $zakazka->load(['zakaznik', 'zarizeni']);

        return Tisk::pdf('pdf.stitek', [
            'z' => $zakazka,
            'qr' => \App\Support\Qr::dataUri($this->stavUrl($zakazka), 260),
        ], 'Stitek-' . $zakazka->cislo, 'stitek');
    }

    public function faktura(Faktura $faktura): Response
    {
        $faktura->load(['zakaznik', 'polozky']);
        $firma = Firma::get();

        return Tisk::pdf('pdf.faktura', [
            'firma' => $firma,
            'f' => $faktura,
            'qrPlatba' => \App\Support\QrPlatba::dataUri(
                $firma->cislo_uctu,
                (float) $faktura->celkem,
                $faktura->variabilni_symbol,
                'Faktura ' . $faktura->cislo,
            ),
        ], 'Faktura-' . $faktura->cislo);
    }

    public function nabidka(Nabidka $nabidka): Response
    {
        $nabidka->load(['zakaznik', 'polozky']);

        return Tisk::pdf('pdf.nabidka', [
            'firma' => Firma::get(),
            'n' => $nabidka,
        ], 'Nabidka-' . $nabidka->cislo);
    }

    public function nahledNabidka(): Response
    {
        return \App\Support\Tisk::nahledNabidka((array) session('nahled_nabidka', []));
    }

    public function nahledFaktura(): Response
    {
        return \App\Support\Tisk::nahledFaktura((array) session('nahled_faktura', []));
    }

    /** Veřejná QR platba (PNG) pro fakturu – vkládá se do e-mailu. */
    public function qrFaktura(Faktura $faktura, string $token): Response
    {
        abort_unless(hash_equals(\App\Support\QrPlatba::token('faktura', $faktura->id), $token), 404);

        $firma = Firma::get();
        $png = \App\Support\QrPlatba::png(
            $firma->cislo_uctu,
            (float) $faktura->celkem,
            $faktura->variabilni_symbol,
            'Faktura ' . $faktura->cislo,
        );

        abort_if($png === null, 404);

        return response($png, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
    }

    /** Veřejná QR platba (PNG) pro doplatek zakázky. */
    public function qrZakazka(Zakazka $zakazka, string $token): Response
    {
        abort_unless(hash_equals(\App\Support\QrPlatba::token('zakazka', $zakazka->id), $token), 404);

        $firma = Firma::get();
        $doplatek = max((float) $zakazka->cena_celkem - (float) $zakazka->zaloha, 0);
        $png = $doplatek > 0
            ? \App\Support\QrPlatba::png($firma->cislo_uctu, $doplatek,
                preg_replace('/\D/', '', $zakazka->cislo), 'Oprava ' . $zakazka->cislo)
            : null;

        abort_if($png === null, 404);

        return response($png, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
    }

    public function obchod(Obchod $obchod): Response
    {
        return Tisk::pdf('pdf.obchod', [
            'firma' => Firma::get(),
            'o' => $obchod,
        ], ($obchod->typ === 'vykup' ? 'Doklad-o-vykupu-' : 'Doklad-o-prodeji-') . $obchod->cislo);
    }
}
