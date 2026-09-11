<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CenikPolozka;
use App\Support\Platformy;
use App\Support\Web;
use Illuminate\Contracts\View\View;

class WebController extends Controller
{
    public function home(): View
    {
        // pár nejčastějších úkonů s cenou jako ochutnávka ceníku
        $ukazkaCeniku = CenikPolozka::query()
            ->where('aktivni', true)->where('na_web', true)
            ->orderBy('poradi')->orderBy('id')
            ->get()
            ->groupBy('kategorie')
            ->map(fn ($g) => $g->take(2))
            ->flatten(1)
            ->take(8);

        return view('web.home', [
            'firma' => Web::firma(),
            'duvera' => Web::duvera(),
            'kroky' => Web::kroky(),
            'dlazdice' => Web::dlazdice(),
            'ukazkaCeniku' => $ukazkaCeniku,
        ]);
    }

    public function jakToFunguje(): View
    {
        return view('web.jak-to-funguje', [
            'firma' => Web::firma(),
            'kroky' => Web::kroky(),
        ]);
    }

    public function oNas(): View
    {
        return view('web.o-nas', ['firma' => Web::firma()]);
    }

    public function reference(): View
    {
        return view('web.reference', [
            'firma' => Web::firma(),
            'recenze' => Web::recenze(),
            'facebookUrl' => Web::facebookUrl(),
        ]);
    }

    public function faq(): View
    {
        return view('web.faq', ['firma' => Web::firma(), 'faq' => Web::faq()]);
    }

    public function kontakt(): View
    {
        return view('web.kontakt', ['firma' => Web::firma()]);
    }

    public function pravni(string $dokument): View
    {
        $mapa = [
            'ochrana-osobnich-udaju' => 'web.pravni.ochrana-osobnich-udaju',
            'reklamacni-rad' => 'web.pravni.reklamacni-rad',
            'cookies' => 'web.pravni.cookies',
        ];

        abort_unless(isset($mapa[$dokument]), 404);

        return view($mapa[$dokument], ['firma' => Web::firma()]);
    }
}
