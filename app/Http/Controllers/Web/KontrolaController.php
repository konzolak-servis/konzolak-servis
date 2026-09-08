<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Zakazka;
use App\Support\QrPlatba;
use App\Support\Web;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class KontrolaController extends Controller
{
    public function form(): View
    {
        return view('web.kontrola', ['firma' => Web::firma()]);
    }

    public function najdi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cislo' => ['required', 'string', 'max:30'],
            'prijmeni' => ['required', 'string', 'max:80'],
            'web' => ['nullable', 'size:0'], // honeypot
        ], [], ['cislo' => 'číslo zakázky', 'prijmeni' => 'příjmení']);

        $klic = 'kontrola:' . $request->ip();
        if (RateLimiter::tooManyAttempts($klic, 8)) {
            return back()->withInput()->with('chyba', 'Příliš mnoho pokusů. Zkuste to za chvíli znovu.');
        }
        RateLimiter::hit($klic, 60);

        $cislo = strtoupper(trim($data['cislo']));
        if (! Str::startsWith($cislo, 'SL-') && preg_match('/^\d{4}-\d+$/', $cislo)) {
            $cislo = 'SL-' . $cislo;
        }

        $prijmeni = Str::lower(trim($data['prijmeni']));

        $zakazka = Zakazka::query()
            ->where('cislo', $cislo)
            ->with('zakaznik')
            ->first();

        $sedi = $zakazka && $zakazka->zakaznik && (
            Str::contains(Str::lower((string) $zakazka->zakaznik->jmeno), $prijmeni)
            || Str::contains(Str::lower((string) $zakazka->zakaznik->firma_nazev), $prijmeni)
        );

        if (! $sedi) {
            return back()->withInput()->with('chyba',
                'Zakázku se nepodařilo najít. Zkontrolujte číslo zakázky i příjmení podle dokladu o převzetí.');
        }

        return redirect()->route('verejne.stav', [
            'zakazka' => $zakazka->id,
            'token' => QrPlatba::token('stav', $zakazka->id),
        ]);
    }
}
