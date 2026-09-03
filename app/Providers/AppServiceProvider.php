<?php

namespace App\Providers;

use App\Mail\BrevoApiTransport;
use App\Models\Prihlaseni;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

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
