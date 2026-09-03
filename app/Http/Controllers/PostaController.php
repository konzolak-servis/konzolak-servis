<?php

namespace App\Http\Controllers;

use App\Support\Posta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'attachments' => ['nullable', 'array'],
        ]);

        $zprava = Posta::ulozPrichozi($data);

        return response()->json([
            'ok' => true,
            'id' => $zprava->id,
            'zakazka_id' => $zprava->zakazka_id,
        ]);
    }
}
