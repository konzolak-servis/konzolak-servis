<?php

namespace App\Http\Controllers\WebAuthn;

use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;

use function response;

class WebAuthnLoginController
{
    /**
     * Returns the challenge to assertion.
     */
    public function options(AssertionRequest $request): Responsable
    {
        return $request->toVerify($request->validate(['email' => 'sometimes|email|string']));
    }

    /**
     * Log the user in – po úspěchu vrátí cílovou URL (zamýšlená stránka, jinak nástěnka).
     */
    public function login(AssertedRequest $request): Response|JsonResponse
    {
        if (! $request->login()) {
            return response()->noContent(422);
        }

        $intended = $request->session()->pull('url.intended');

        return response()->json([
            'redirect' => $intended ?: Filament::getUrl(),
        ]);
    }
}
