<?php

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

use function response;

class WebAuthnRegisterController
{
    /**
     * Returns a challenge to be verified by the user device.
     */
    public function options(AttestationRequest $request): Responsable
    {
        abort_unless((bool) $request->user()?->jeAdmin(), 403);

        // Discoverable credential + ověření uživatele (otisk/PIN) → přihlášení bez jména i hesla.
        return $request
            ->userless()
            ->secureRegistration()
            ->toCreate();
    }

    /**
     * Registers a device for further WebAuthn authentication.
     */
    public function register(AttestedRequest $request): Response
    {
        abort_unless((bool) $request->user()?->jeAdmin(), 403);

        $request->save();

        return response()->noContent();
    }
}
