<?php

namespace App\Support;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * QR kód jako data-URI (SVG) – vhodné pro mPDF. Degraduje na prázdný řetězec při chybě.
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
}
