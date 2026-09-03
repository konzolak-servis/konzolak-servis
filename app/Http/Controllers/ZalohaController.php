<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ZalohaController extends Controller
{
    /** Seznam ZIP záloh, nejnovější první. */
    public static function seznam(): \Illuminate\Support\Collection
    {
        $dir = storage_path('zalohy');

        if (! File::isDirectory($dir)) {
            return collect();
        }

        return collect(File::files($dir))
            ->filter(fn ($f) => str_starts_with($f->getFilename(), 'zaloha_') && strtolower($f->getExtension()) === 'zip')
            ->sortByDesc(fn ($f) => $f->getFilename())
            ->values();
    }

    /** Stáhne nejnovější zálohu jako soubor (offline kopie). */
    public function stahnout(): BinaryFileResponse
    {
        $nejnovejsi = self::seznam()->first();

        abort_if($nejnovejsi === null, 404, 'Zatím není žádná záloha k dispozici.');

        return response()->download($nejnovejsi->getPathname());
    }
}
