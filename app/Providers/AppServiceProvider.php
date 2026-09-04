<?php

namespace App\Providers;

use App\Mail\BrevoApiTransport;
use App\Models\Prihlaseni;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Laragear\WebAuthn\Assertion\Validator\AssertionValidation;
use Laragear\WebAuthn\Assertion\Validator\AssertionValidator;
use Laragear\WebAuthn\Auth\WebAuthnUserProvider;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\JsonTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('brevo-api', function (array $config) {
            return new BrevoApiTransport((string) config('services.brevo.key'));
        });

        // WebAuthn – při neúspěšné asserci zaloguj skutečný důvod (jinak ho laragear
        // spolkne, když APP_DEBUG=false).
        WebAuthnUserProvider::validateUsing(function ($user, array $credentials): ?bool {
            if (! $user instanceof WebAuthnAuthenticatable
                || ! isset($credentials['id'], $credentials['rawId'], $credentials['response'], $credentials['type'])) {
                return null; // není to WebAuthn assertion → nech laragear rozhodnout
            }

            try {
                app(AssertionValidator::class)
                    ->send(new AssertionValidation(new JsonTransport($credentials), $user))
                    ->thenReturn();

                return true;
            } catch (\Throwable $e) {
                Log::warning('WebAuthn assertion selhala: ' . $e::class . ' — ' . $e->getMessage());

                return false;
            }
        });

        // Záznam každého úspěšného přihlášení do tabulky `prihlaseni`.
        Event::listen(Login::class, function (Login $event): void {
            try {
                Prihlaseni::create([
                    'user_id' => $event->user->getAuthIdentifier(),
                    'jmeno' => $event->user->name ?? null,
                    'ip' => request()->ip(),
                    'prohlizec' => substr((string) request()->userAgent(), 0, 255),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // logování přihlášení nikdy nesmí shodit přihlášení samotné
            }
        });
    }
}
