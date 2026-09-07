<?php

namespace App\Http\Controllers;

use App\Models\Zprava;
use App\Support\Posta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostaController extends Controller
{
    /**
     * Příjem e-mailu z Cloudflare Email Workeru.
     * Chráněno sdíleným tokenem v hlavičce X-Posta-Token.
     */
    public function prijem(Request $request): JsonResponse
    {
        $token = config('services.posta.token');

        if (! $token || ! hash_equals($token, (string) $request->header('X-Posta-Token'))) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $data = $request->validate([
            'from' => ['required', 'string', 'max:255'],
            'fromName' => ['nullable', 'string', 'max:255'],
            'to' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:998'],
            'text' => ['nullable', 'string'],
            'html' => ['nullable', 'string'],
            'messageId' => ['nullable', 'string', 'max:255'],
            'inReplyTo' => ['nullable', 'string', 'max:255'],
            'references' => ['nullable', 'string'],
            'date' => ['nullable', 'string', 'max:100'],
            'spam' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['array'],
        ]);

        $zprava = Posta::ulozPrichozi($data);

        return response()->json([
            'ok' => true,
            'id' => $zprava->id,
            'zakazka_id' => $zprava->zakazka_id,
            'prilohy' => is_array($zprava->prilohy) ? count($zprava->prilohy) : 0,
        ]);
    }

    /** Stažení uložené přílohy příchozí zprávy (jen pro přihlášené). */
    public function priloha(Zprava $zprava, int $index): StreamedResponse
    {
        $prilohy = is_array($zprava->prilohy) ? array_values($zprava->prilohy) : [];
        $p = $prilohy[$index] ?? null;

        abort_unless($p && ! empty($p['soubor']) && Storage::disk('local')->exists($p['soubor']), 404);

        return Storage::disk('local')->download($p['soubor'], $p['nazev'] ?? 'priloha');
    }
}
