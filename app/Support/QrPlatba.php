<?php

namespace App\Support;

use App\Models\Firma;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Česká „QR Platba" (formát SPAYD) – načtou ji všechny tuzemské bankovní aplikace.
 */
class QrPlatba
{
    /**
     * SPAYD řetězec.
     *
     * @param  string|null  $vs    variabilní symbol (jen číslice)
     * @param  string|null  $ucel  popis platby (za co) – doplní se název firmy
     */
    public static function spayd(?string $ucet, float $castka, ?string $vs = null, ?string $ucel = null): ?string
    {
        $iban = self::ibanZUctu($ucet);
        if (! $iban) {
            return null;
        }

        $parts = [
            'SPD', '1.0',
            'ACC:' . $iban,
            'AM:' . number_format($castka, 2, '.', ''),
            'CC:CZK',
        ];

        $vs = preg_replace('/\D/', '', (string) $vs);
        if ($vs !== '') {
            $parts[] = 'X-VS:' . substr($vs, 0, 10);
        }

        // do zprávy vždy název firmy + popis platby
        $firma = Firma::get()->nazev ?: 'Konzolák Zlín';
        $msg = trim($firma . ($ucel ? ' - ' . $ucel : ''));
        $parts[] = 'MSG:' . self::ocisti($msg);

        return implode('*', $parts);
    }

    /** QR platba jako data-URI (SVG), střední korekce chyb kvůli tisku. */
    public static function dataUri(?string $ucet, float $castka, ?string $vs = null, ?string $ucel = null, int $size = 240): string
    {
        $spayd = self::spayd($ucet, $castka, $vs, $ucel);
        if (! $spayd || ! class_exists(Builder::class)) {
            return '';
        }

        try {
            $result = (new Builder(
                writer: new SvgWriter(),
                data: $spayd,
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: $size,
                margin: 2,
            ))->build();

            return $result->getDataUri();
        } catch (\Throwable) {
            return '';
        }
    }

    /** QR platba jako PNG (binární obsah) – pro vložení do e-mailu přes veřejnou URL. */
    public static function png(?string $ucet, float $castka, ?string $vs = null, ?string $ucel = null, int $size = 300): ?string
    {
        $spayd = self::spayd($ucet, $castka, $vs, $ucel);
        if (! $spayd || ! class_exists(Builder::class)) {
            return null;
        }

        try {
            $result = (new Builder(
                writer: new PngWriter(),
                data: $spayd,
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: $size,
                margin: 2,
            ))->build();

            return $result->getString();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Krátký nehádatelný token pro veřejnou QR URL. */
    public static function token(string $typ, int $id): string
    {
        return substr(hash_hmac('sha256', $typ . ':' . $id, (string) config('app.key')), 0, 16);
    }

    /** Převod tuzemského čísla účtu na IBAN (CZ). */
    public static function ibanZUctu(?string $ucet): ?string
    {
        if (! $ucet) {
            return null;
        }
        $ucet = trim($ucet);

        if (preg_match('/^CZ\d{22}$/i', str_replace(' ', '', $ucet))) {
            return strtoupper(str_replace(' ', '', $ucet));
        }

        if (! preg_match('~^(?:(\d{1,6})-)?(\d{2,10})/(\d{4})$~', $ucet, $m)) {
            return null;
        }

        [, $predcisli, $cislo, $kod] = $m;
        $bban = $kod
            . str_pad($predcisli ?: '0', 6, '0', STR_PAD_LEFT)
            . str_pad($cislo, 10, '0', STR_PAD_LEFT);

        $numeric = $bban . '123500';
        $mod = 0;
        foreach (str_split($numeric) as $d) {
            $mod = ($mod * 10 + (int) $d) % 97;
        }
        $check = str_pad((string) (98 - $mod), 2, '0', STR_PAD_LEFT);

        return 'CZ' . $check . $bban;
    }

    private static function ocisti(string $s): string
    {
        $s = strtr($s, [
            'á' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'í' => 'i', 'ň' => 'n',
            'ó' => 'o', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u', 'ý' => 'y', 'ž' => 'z',
            'Á' => 'A', 'Č' => 'C', 'É' => 'E', 'Ě' => 'E', 'Í' => 'I', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T',
            'Ú' => 'U', 'Ý' => 'Y', 'Ž' => 'Z', 'Ó' => 'O', 'Ň' => 'N', 'Ď' => 'D',
            '*' => ' ',
        ]);

        return mb_substr(trim($s), 0, 60);
    }
}
