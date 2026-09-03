<?php

namespace App\Support;

use Illuminate\Http\Response;
use Mpdf\Mpdf;

class Tisk
{
    /**
     * Vyrenderuje blade šablonu do PDF a vrátí ji k zobrazení v prohlížeči (k tisku).
     * $format: 'a4' (výchozí) nebo 'stitek' (malý štítek na zařízení 90 × 50 mm).
     */
    public static function pdf(string $view, array $data, string $filename, string $format = 'a4'): Response
    {
        $tmp = storage_path('app/mpdf');
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $config = $format === 'stitek'
            ? [
                'format' => [90, 50],
                'margin_left' => 3, 'margin_right' => 3, 'margin_top' => 3, 'margin_bottom' => 3,
                'default_font_size' => 8,
            ]
            : [
                'format' => 'A4',
                'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 16,
                'margin_bottom' => 18, 'margin_footer' => 8,
                'default_font_size' => 9.5,
            ];

        $mpdf = new Mpdf(array_merge([
            'mode' => 'utf-8',
            'default_font' => 'dejavusans',
            'tempDir' => $tmp,
        ], $config));

        $mpdf->SetTitle($filename);
        $mpdf->SetAuthor('Konzolák Zlín');
        // Patičku (podpisy + meta) si nastavuje každá A4 šablona přes <htmlpagefooter>.
        $mpdf->WriteHTML(view($view, $data)->render());

        return response($mpdf->Output($filename . '.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
        ]);
    }

    /** Náhled nabídky z rozpracovaného (neuloženého) formuláře. */
    public static function nahledNabidka(array $data): Response
    {
        $polozkyRaw = $data['polozky'] ?? [];
        $zakaznikId = $data['zakaznik_id'] ?? null;
        unset($data['polozky']);

        $n = (new \App\Models\Nabidka)->forceFill($data);
        $n->cislo = 'NÁHLED';
        $n->setRelation('zakaznik', $zakaznikId ? \App\Models\Zakaznik::find($zakaznikId) : null);

        $polozky = self::radky($polozkyRaw, \App\Models\NabidkaPolozka::class);
        $n->setRelation('polozky', $polozky);
        $n->celkem = $polozky->sum('cena_celkem');

        return self::pdf('pdf.nabidka', ['firma' => \App\Models\Firma::get(), 'n' => $n], 'Nahled-nabidky');
    }

    /** Náhled faktury z rozpracovaného formuláře. */
    public static function nahledFaktura(array $data): Response
    {
        $polozkyRaw = $data['polozky'] ?? [];
        $zakaznikId = $data['zakaznik_id'] ?? null;
        unset($data['polozky']);

        $f = (new \App\Models\Faktura)->forceFill($data);
        $f->cislo = 'NÁHLED';
        $f->variabilni_symbol = 'náhled';
        $f->setRelation('zakaznik', $zakaznikId ? \App\Models\Zakaznik::find($zakaznikId) : null);

        $polozky = self::radky($polozkyRaw, \App\Models\FakturaPolozka::class);
        $f->setRelation('polozky', $polozky);
        $f->celkem = $polozky->sum('cena_celkem');

        return self::pdf('pdf.faktura', [
            'firma' => \App\Models\Firma::get(),
            'f' => $f,
            'qrPlatba' => '',
        ], 'Nahled-faktury');
    }

    /** Pole řádků z repeateru → Eloquent kolekce modelů s dopočtenou cenou. */
    private static function radky(array $raw, string $model): \Illuminate\Database\Eloquent\Collection
    {
        $items = collect(array_values($raw))->map(function ($p) use ($model) {
            $m = (new $model)->forceFill(is_array($p) ? $p : (array) $p);
            $m->mnozstvi = (float) ($m->mnozstvi ?: 1);
            $m->cena = (float) ($m->cena ?: 0);
            $m->cena_celkem = round($m->mnozstvi * $m->cena, 2);

            return $m;
        })->all();

        return new \Illuminate\Database\Eloquent\Collection($items);
    }
}
