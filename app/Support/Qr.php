<?php

namespace App\Support;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * QR kód jako data-URI – vhodné pro mPDF. Degraduje na prázdný řetězec při chybě.
 */
class Qr
{
    public static function dataUri(string $text, int $size = 220): string
    {
        if (! class_exists(Builder::class)) {
            return '';
        }

        try {
            $result = (new Builder(
                writer: new SvgWriter(),
                data: $text,
                size: $size,
                margin: 4,
            ))->build();

            return $result->getDataUri();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * QR kód s černým logem Konzolák uprostřed (PNG) – bez rámečku, moduly kódu
     * logo obtékají podle jeho tvaru. Vysoká korekce chyb (30 %) chybějící moduly dopočítá.
     */
    public static function sLogemDataUri(string $text, float $logoPodil = 0.34): string
    {
        $logoSoubor = public_path('images/konzolak-icon.png');
        if (! class_exists(Builder::class) || ! is_file($logoSoubor) || ! extension_loaded('gd')) {
            return self::dataUri($text, 600);
        }

        try {
            $matice = (new Builder(
                writer: new PngWriter(),
                data: $text,
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 100,
                margin: 0,
            ))->build()->getMatrix();

            $n = $matice->getBlockCount();
            $m = 16;                       // px na modul
            $okraj = 2 * $m;               // tichá zóna (štítek má vlastní okraj)
            $px = $n * $m + 2 * $okraj;

            // logo: ořez průhledných okrajů, přebarvení na černo, zmenšení na podíl šířky kódu
            $zdroj = imagecreatefrompng($logoSoubor);
            imagesavealpha($zdroj, true);
            $zdroj = imagecropauto($zdroj, IMG_CROP_TRANSPARENT) ?: $zdroj;
            $lw = (int) round($n * $m * $logoPodil);
            $lh = (int) round(imagesy($zdroj) * $lw / imagesx($zdroj));
            $logo = imagecreatetruecolor($lw, $lh);
            imagealphablending($logo, false);
            imagesavealpha($logo, true);
            imagefill($logo, 0, 0, imagecolorallocatealpha($logo, 0, 0, 0, 127));
            imagecopyresampled($logo, $zdroj, 0, 0, 0, 0, $lw, $lh, imagesx($zdroj), imagesy($zdroj));
            imagefilter($logo, IMG_FILTER_BRIGHTNESS, -255);
            $lx = (int) round(($px - $lw) / 2);
            $ly = (int) round(($px - $lh) / 2);

            // které moduly logo zakrývá (s odstupem ~0,6 modulu kolem tvaru)
            $odstup = (int) round($m * 0.6);
            $kryto = [];
            for ($y = 0; $y < $lh; $y += 2) {
                for ($x = 0; $x < $lw; $x += 2) {
                    if (((imagecolorat($logo, $x, $y) >> 24) & 0x7F) > 63) {
                        continue; // průhledný pixel
                    }
                    $gx = $lx + $x - $okraj;
                    $gy = $ly + $y - $okraj;
                    for ($r = intdiv(max(0, $gy - $odstup), $m); $r <= min($n - 1, intdiv($gy + $odstup, $m)); $r++) {
                        for ($c = intdiv(max(0, $gx - $odstup), $m); $c <= min($n - 1, intdiv($gx + $odstup, $m)); $c++) {
                            $kryto["$r,$c"] = true;
                        }
                    }
                }
            }

            $img = imagecreatetruecolor($px, $px);
            imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
            $cerna = imagecolorallocate($img, 0, 0, 0);
            for ($r = 0; $r < $n; $r++) {
                for ($c = 0; $c < $n; $c++) {
                    if ($matice->getBlockValue($r, $c) === 1 && ! isset($kryto["$r,$c"])) {
                        $x = $okraj + $c * $m;
                        $y = $okraj + $r * $m;
                        imagefilledrectangle($img, $x, $y, $x + $m - 1, $y + $m - 1, $cerna);
                    }
                }
            }
            imagealphablending($img, true);
            imagecopy($img, $logo, $lx, $ly, 0, 0, $lw, $lh);

            ob_start();
            imagepng($img);

            return 'data:image/png;base64,' . base64_encode(ob_get_clean());
        } catch (\Throwable) {
            return self::dataUri($text, 600);
        }
    }
}
