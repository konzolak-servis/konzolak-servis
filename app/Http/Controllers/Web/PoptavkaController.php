<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Platformy;
use App\Support\Posta;
use App\Support\Web;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PoptavkaController extends Controller
{
    public function odeslat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jmeno' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'telefon' => ['nullable', 'string', 'max:40'],
            'platforma' => ['nullable', 'string', 'max:40'],
            'zprava' => ['required', 'string', 'max:5000'],
            'web' => ['nullable', 'size:0'], // honeypot
        ], [], [
            'jmeno' => 'jméno', 'email' => 'e-mail', 'zprava' => 'zpráva',
        ]);

        $klic = 'poptavka:' . $request->ip();
        if (RateLimiter::tooManyAttempts($klic, 5)) {
            return back()->withInput()->with('chyba', 'Příliš mnoho odeslání. Zkuste to prosím za chvíli.');
        }
        RateLimiter::hit($klic, 300);

        $platformaLabel = $data['platforma'] ? Platformy::label($data['platforma']) : null;

        $telo = collect([
            'Jméno: ' . $data['jmeno'],
            'E-mail: ' . $data['email'],
            $data['telefon'] ? 'Telefon: ' . $data['telefon'] : null,
            $platformaLabel ? 'Zařízení: ' . $platformaLabel : null,
            '',
            $data['zprava'],
        ])->filter(fn ($r) => $r !== null)->implode("\n");

        Posta::ulozPrichozi([
            'from' => Str::lower($data['email']),
            'fromName' => $data['jmeno'],
            'to' => Web::firma()->email,
            'subject' => 'Web – poptávka opravy' . ($platformaLabel ? ' (' . $platformaLabel . ')' : ''),
            'text' => $telo,
            'messageId' => 'web-' . Str::uuid() . '@konzolak.com',
            'date' => now()->toRfc2822String(),
        ]);

        return back()->with('odeslano', true);
    }
}
