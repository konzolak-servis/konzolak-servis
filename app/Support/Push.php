<?php

namespace App\Support;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/** Odesílání webových push notifikací (VAPID) přihlášeným uživatelům systému. */
class Push
{
    private static function klic(): array
    {
        return [
            'VAPID' => [
                'subject' => 'mailto:' . (config('mail.from.address') ?: 'servis@konzolak.com'),
                'publicKey' => (string) config('services.push.public_key'),
                'privateKey' => (string) config('services.push.private_key'),
            ],
        ];
    }

    /** Je push v konfiguraci zapnutý (jsou nastavené klíče)? */
    public static function aktivni(): bool
    {
        return filled(config('services.push.public_key')) && filled(config('services.push.private_key'));
    }

    /** Pošle notifikaci všem admin uživatelům (výchozí – nová pošta/poptávka vidí jen admin). */
    public static function posliAdminum(string $titulek, string $telo, ?string $url = null): void
    {
        if (! self::aktivni()) {
            return;
        }

        self::posliUzivatelum(User::where('je_admin', true)->get(), $titulek, $telo, $url);
    }

    public static function posliUzivatelum(iterable $uzivatele, string $titulek, string $telo, ?string $url = null): void
    {
        if (! self::aktivni()) {
            return;
        }

        $subscriptions = PushSubscription::whereIn('user_id', collect($uzivatele)->pluck('id'))->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $webPush = new WebPush(self::klic());
        $payload = json_encode([
            'title' => $titulek,
            'body' => $telo,
            'url' => $url ?: rtrim(config('app.url'), '/') . '/admin',
        ], JSON_UNESCAPED_UNICODE);

        foreach ($subscriptions as $s) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $s->endpoint,
                    'publicKey' => $s->public_key,
                    'authToken' => $s->auth_token,
                    'contentEncoding' => $s->content_encoding,
                ]),
                $payload,
            );
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            // Endpoint už neplatí (uživatel odhlásil oznámení / smazal appku) – uklidíme.
            if (in_array($report->getResponse()?->getStatusCode(), [404, 410], true)) {
                PushSubscription::where('endpoint', $report->getRequest()->getUri())->delete();
                continue;
            }

            Log::warning('Push notifikace se nepodařila odeslat', [
                'endpoint' => $report->getEndpoint(),
                'reason' => $report->getReason(),
            ]);
        }
    }
}
