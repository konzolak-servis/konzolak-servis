<?php

namespace App\Http\Controllers;

use App\Models\Faktura;
use App\Models\Firma;
use App\Models\Nabidka;
use App\Models\Obchod;
use App\Models\Zakazka;
use App\Models\Zapujcka;
use App\Models\ZapujcniZarizeni;
use App\Support\Qr;
use App\Support\QrPlatba;
use App\Support\Tisk;
use Illuminate\Http\Response;

class TiskController extends Controller
{
    /** Veřejná URL stavu zakázky (do QR na dokladu / štítku / protokolu). */
    private function stavUrl(Zakazka $zakazka): string
    {
        return route('verejne.stav', [
            'zakazka' => $zakazka->id,
            'token' => QrPlatba::token('stav', $zakazka->id),
        ]);
    }

    public function servisniDoklad(Zakazka $zakazka): Response
    {
        $zakazka->load(['zakaznik', 'zarizeni', 'polozky']);

        return Tisk::pdf('pdf.servisni-doklad', [
            'firma' => Firma::get(),
            'z' => $zakazka,
            'qr' => Qr::dataUri($this->stavUrl($zakazka), 200),
        ], 'Doklad-o-prevzeti-'.$zakazka->cislo);
    }

    public function servisniProtokol(Zakazka $zakazka): Response
    {
        $zakazka->load(['zakaznik', 'zarizeni', 'polozky', 'reklamaceK']);

        $firma = Firma::get();
        $doplatek = $zakazka->doplatek();

        return Tisk::pdf('pdf.servisni-protokol', [
            'firma' => $firma,
            'z' => $zakazka,
            'jeProtokol' => true,
            'qr' => Qr::dataUri($this->stavUrl($zakazka), 180),
            'qrPlatba' => $zakazka->zpusob_uhrady === 'ucet' && $doplatek > 0 && ! $zakazka->jeDobirka()
                ? QrPlatba::dataUri($firma->cislo_uctu, $doplatek,
                    preg_replace('/\D/', '', $zakazka->cislo), 'Oprava '.$zakazka->cislo, 200)
                : '',
        ], 'Servisni-protokol-'.$zakazka->cislo);
    }

    public function stitek(Zakazka $zakazka): Response
    {
        $zakazka->load(['zakaznik', 'zarizeni']);

        return Tisk::pdf('pdf.stitek', [
            'z' => $zakazka,
            'qr' => Qr::sLogemDataUri($this->stavUrl($zakazka)),
        ], 'Stitek-'.$zakazka->cislo, 'stitek');
    }

    /** Štítek jako PNG ke stažení (600 DPI, 50 × 30 mm = 1181 × 709 px) – převod PDF přes Ghostscript. */
    public function stitekPng(Zakazka $zakazka): Response
    {
        return $this->pngZPdf($this->stitek($zakazka)->getContent(), 'Stitek-' . $zakazka->cislo . '.png');
    }

    /** Štítek(y) 50 × 30 mm na zápůjční zařízení – jedno, nebo víc (?ids=1,2,3 → stránka na štítek). */
    public function zapujcniStitek(?ZapujcniZarizeni $zarizeni = null): Response
    {
        $ids = $zarizeni ? [$zarizeni->id] : array_filter(array_map('intval', explode(',', (string) request()->query('ids'))));
        $zarizeni = ZapujcniZarizeni::whereKey($ids)->orderBy('nazev')->get();
        abort_if($zarizeni->isEmpty(), 404);

        return Tisk::pdf('pdf.stitek-zapujcni', [
            'firma' => Firma::get(),
            'zarizeni' => $zarizeni->map(fn ($d) => [
                'd' => $d,
                'qr' => Qr::sLogemDataUri(\App\Filament\Resources\ZapujcniZarizenis\ZapujcniZarizeniResource::getUrl('edit', ['record' => $d])),
            ]),
        ], 'Stitek-zapujcka-' . ($zarizeni->count() === 1 ? $zarizeni->first()->id : 'vice'), 'stitek');
    }

    public function zapujcniStitekPng(ZapujcniZarizeni $zarizeni): Response
    {
        return $this->pngZPdf($this->zapujcniStitek($zarizeni)->getContent(),
            'Stitek-zapujcka-' . \Illuminate\Support\Str::slug($zarizeni->nazev) . '-' . $zarizeni->id . '.png');
    }

    /** PDF (1 strana) → PNG 600 DPI ke stažení přes Ghostscript. */
    private function pngZPdf(string $pdf, string $nazevSouboru): Response
    {
        $adr = storage_path('app/mpdf');
        $vstup = tempnam($adr, 'stitek') . '.pdf';
        $vystup = substr($vstup, 0, -4) . '.png';
        file_put_contents($vstup, $pdf);

        try {
            $gs = \Illuminate\Support\Facades\Process::timeout(30)->run([
                PHP_OS_FAMILY === 'Windows' ? 'gswin64c' : 'gs',
                '-q', '-dSAFER', '-dBATCH', '-dNOPAUSE',
                '-sDEVICE=png16m', '-r600', '-dTextAlphaBits=4', '-dGraphicsAlphaBits=4',
                '-sOutputFile=' . $vystup, $vstup,
            ]);
            abort_unless($gs->successful() && is_file($vystup), 500, 'Převod štítku na PNG se nepodařil (chybí Ghostscript?).');
            $png = file_get_contents($vystup);
        } finally {
            @unlink($vstup);
            @unlink($vystup);
            @unlink(substr($vstup, 0, -4)); // prázdný soubor z tempnam
        }

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $nazevSouboru . '"',
        ]);
    }

    public function zapujckaProtokol(Zapujcka $zapujcka): Response
    {
        return $this->zapujckaPdf($zapujcka, 'pdf.zapujcka-protokol', 'Predavaci-protokol-');
    }

    public function zapujckaKauce(Zapujcka $zapujcka): Response
    {
        return $this->zapujckaPdf($zapujcka, 'pdf.zapujcka-kauce', 'Prijem-kauce-');
    }

    public function zapujckaVraceni(Zapujcka $zapujcka): Response
    {
        return $this->zapujckaPdf($zapujcka, 'pdf.zapujcka-vraceni', 'Vraceni-zapujcky-');
    }

    private function zapujckaPdf(Zapujcka $zapujcka, string $view, string $soubor): Response
    {
        $zapujcka->load(['zakaznik', 'zakazka', 'polozky']);

        return Tisk::pdf($view, ['firma' => Firma::get(), 'z' => $zapujcka], $soubor . $zapujcka->cislo);
    }

    public function faktura(Faktura $faktura): Response
    {
        $faktura->load(['zakaznik', 'polozky']);
        $firma = Firma::get();

        return Tisk::pdf('pdf.faktura', [
            'firma' => $firma,
            'f' => $faktura,
            'qrPlatba' => QrPlatba::dataUri(
                $firma->cislo_uctu,
                (float) $faktura->celkem,
                $faktura->variabilni_symbol,
                'Faktura '.$faktura->cislo,
            ),
        ], 'Faktura-'.$faktura->cislo);
    }

    public function nabidka(Nabidka $nabidka): Response
    {
        $nabidka->load(['zakaznik', 'polozky']);

        return Tisk::pdf('pdf.nabidka', [
            'firma' => Firma::get(),
            'n' => $nabidka,
        ], 'Nabidka-'.$nabidka->cislo);
    }

    public function nahledNabidka(): Response
    {
        return Tisk::nahledNabidka((array) session('nahled_nabidka', []));
    }

    public function nahledFaktura(): Response
    {
        return Tisk::nahledFaktura((array) session('nahled_faktura', []));
    }

    /** Veřejná QR platba (PNG) pro fakturu – vkládá se do e-mailu. */
    public function qrFaktura(Faktura $faktura, string $token): Response
    {
        abort_unless(hash_equals(QrPlatba::token('faktura', $faktura->id), $token), 404);

        $firma = Firma::get();
        $png = QrPlatba::png(
            $firma->cislo_uctu,
            (float) $faktura->celkem,
            $faktura->variabilni_symbol,
            'Faktura '.$faktura->cislo,
        );

        abort_if($png === null, 404);

        return response($png, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
    }

    /** Veřejná QR platba (PNG) pro doplatek zakázky. */
    public function qrZakazka(Zakazka $zakazka, string $token): Response
    {
        abort_unless(hash_equals(QrPlatba::token('zakazka', $zakazka->id), $token), 404);

        $firma = Firma::get();
        $doplatek = $zakazka->doplatek();
        $png = $doplatek > 0 && ! $zakazka->jeDobirka()
            ? QrPlatba::png($firma->cislo_uctu, $doplatek,
                preg_replace('/\D/', '', $zakazka->cislo), 'Oprava '.$zakazka->cislo)
            : null;

        abort_if($png === null, 404);

        return response($png, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
    }

    public function obchod(Obchod $obchod): Response
    {
        // Bazarová položka se pořizuje jako „výkup" a později se prodá – doklad o prodeji
        // se vyžádá parametrem ?typ=prodej (a musí být opravdu prodáno).
        $jakoProdej = request()->query('typ') === 'prodej' && $obchod->prodano;

        return Tisk::pdf('pdf.obchod', [
            'firma' => Firma::get(),
            'o' => $obchod,
            'jakoProdej' => $jakoProdej,
        ], ($jakoProdej ? 'Doklad-o-prodeji-' : 'Doklad-o-vykupu-').$obchod->cislo);
    }
}
