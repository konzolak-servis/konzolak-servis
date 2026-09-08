<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CenikPolozka;
use App\Support\Platformy;
use App\Support\Web;
use Illuminate\Contracts\View\View;

class CenikController extends Controller
{
    public function index(): View
    {
        $polozky = CenikPolozka::query()
            ->where('aktivni', true)->where('na_web', true)
            ->orderBy('poradi')->orderBy('id')
            ->get();

        // seskupení podle kanonického pořadí platforem
        $poradi = array_keys(Platformy::HODNOTY);
        $skupiny = $polozky
            ->groupBy('kategorie')
            ->sortBy(fn ($g, $k) => array_search($k, $poradi, true) === false ? 999 : array_search($k, $poradi, true));

        return view('web.cenik', [
            'firma' => Web::firma(),
            'skupiny' => $skupiny,
            'label' => fn (?string $k) => Platformy::label($k),
        ]);
    }
}
