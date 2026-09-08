<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CenikPolozka;
use App\Support\Platformy;
use App\Support\Web;
use Illuminate\Contracts\View\View;

class OpravyController extends Controller
{
    public function index(): View
    {
        return view('web.opravy.index', [
            'firma' => Web::firma(),
            'sluzby' => Web::sluzby(),
        ]);
    }

    public function detail(string $slug): View
    {
        $sluzby = Web::sluzby();
        abort_unless(isset($sluzby[$slug]), 404);

        $s = $sluzby[$slug];

        $cenik = CenikPolozka::query()
            ->where('aktivni', true)->where('na_web', true)
            ->whereIn('kategorie', $s['platformy'])
            ->orderBy('poradi')->orderBy('id')
            ->get();

        return view('web.opravy.detail', [
            'firma' => Web::firma(),
            'slug' => $slug,
            's' => $s,
            'cenik' => $cenik,
            'labelPlatformy' => fn (string $k) => Platformy::label($k),
        ]);
    }
}
