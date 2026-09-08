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
            'overeni' => ['required', 'string', 'min:3', 'max:120'],
            'web' => ['nullable', 'size:0'], // honeypot
        ], [], ['cislo' => 'číslo zakázky', 'overeni' => 'příjmení, e-mail nebo telefon']);

        $klic = 'kontrola:' . $request->ip();
        if (RateLimiter::tooManyAttempts($klic, 8)) {
            return back()->withInput()->with('chyba', 'Příliš mnoho pokusů. Zkuste to za chvíli znovu.');
        }
        RateLimiter::hit($klic, 60);

        $cislo = strtoupper(trim($data['cislo']));
        if (! Str::startsWith($cislo, 'SL-') && preg_match('/^\d{4}-\d+$/', $cislo)) {
            $cislo = 'SL-' . $cislo;
        }

        // porovnání bez ohledu na velikost písmen a diakritiku
        $norm = fn (?string $s) => Str::lower(Str::ascii((string) $s));
        $vstup = $norm(trim($data['overeni']));
        $vstupTel = preg_replace('/\D+/', '', $data['overeni']);

        $zakazka = Zakazka::query()->where('cislo', $cislo)->with('zakaznik')->first();
        $k = $zakazka?->zakaznik;

        $sedi = $k && (
            ($vstup !== '' && str_contains($norm($k->jmeno), $vstup))
            || ($vstup !== '' && str_contains($norm($k->firma_nazev), $vstup))
            || (filled($k->email) && $norm($k->email) === $vstup)
            || (strlen($vstupTel) >= 6 && filled($k->telefon)
                && str_contains(preg_replace('/\D+/', '', $k->telefon), $vstupTel))
        );

        if (! $sedi) {
            return back()->withInput()->with('chyba',
                'Zakázku se nepodařilo najít. Zkontrolujte číslo zakázky a druhý údaj (příjmení, e-mail nebo telefon) podle dokladu o převzetí.');
        }

        return redirect()->route('verejne.stav', [
            'zakazka' => $zakazka->id,
            'token' => QrPlatba::token('stav', $zakazka->id),
        ]);
    }
}
