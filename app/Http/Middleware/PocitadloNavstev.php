<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Počítadlo návštěv veřejného webu (konzolak.com). Započítá úspěšně zobrazenou stránku
 * webu (GET, routy web.*) – ne administraci, ne přihlášené uživatele servisu, ne roboty.
 * Návštěvník = jednosměrný otisk IP + prohlížeče + dne, IP adresa se neukládá.
 * Chyba počítadla nikdy nerozbije stránku.
 */
class PocitadloNavstev
{
    private const ROBOTI = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|curl|wget|python|httpclient|headless|lighthouse|monitor/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($this->zapocitat($request, $response)) {
                $this->zapsat($request);
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function zapocitat(Request $request, Response $response): bool
    {
        $ua = (string) $request->userAgent();

        return $request->isMethod('GET')
            && $response->isSuccessful()
            && $request->routeIs('web.*')
            && ! str_starts_with($request->getHost(), 'servis.')
            && ! $request->user()
            && $ua !== ''
            && ! preg_match(self::ROBOTI, $ua);
    }

    private function zapsat(Request $request): void
    {
        $den = now()->toDateString();
        $otisk = hash('sha256', $request->ip() . '|' . $request->userAgent() . '|' . $den . '|' . config('app.key'));
        $odkud = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        DB::table('navstevy_webu')->upsert(
            [[
                'den' => $den,
                'navstevnik' => $otisk,
                'zobrazeni' => 1,
                'vstupni_stranka' => mb_substr('/' . ltrim($request->path(), '/'), 0, 191),
                'odkud' => $odkud && $odkud !== $request->getHost() ? mb_substr($odkud, 0, 191) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['den', 'navstevnik'],
            ['zobrazeni' => DB::raw('zobrazeni + 1'), 'updated_at' => now()],
        );
    }
}
