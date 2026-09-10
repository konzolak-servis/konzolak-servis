<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Přihlašovací brána veřejného webu (konzolak.com), dokud web není spuštěn.
 * Aktivní jen pro apex doménu a jen když je v konfiguraci nastavené heslo.
 * Servisní systém (servis.konzolak.com) se jí netýká.
 */
class WebGate
{
    /** Hosty, na kterých brána platí. */
    private const HOSTY = ['konzolak.com', 'www.konzolak.com'];

    /** Cesty, které projdou i bez přihlášení (odemčení, statické soubory, health). */
    private const VOLNE = [
        'vstup', 'up', 'favicon.ico', 'robots.txt',
        'css/*', 'js/*', 'images/*', 'build/*', 'storage/*', '.well-known/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $heslo = (string) config('web.gate_heslo');

        if ($heslo === '' || ! in_array($request->getHost(), self::HOSTY, true)) {
            return $next($request);
        }

        if ($request->session()->get('web_gate_ok') === true || $request->is(...self::VOLNE)) {
            return $next($request);
        }

        return response()->view('web.ve-vystavbe', [], 503)
            ->header('Retry-After', '86400');
    }
}
