<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PushController extends Controller
{
    /** Uloží přihlášení k odběru push notifikací pro přihlášeného uživatele. */
    public function subscribe(Request $request): Response
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'zarizeni' => ['nullable', 'string', 'max:120'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'zarizeni' => $data['zarizeni'] ?? null,
            ],
        );

        return response()->noContent();
    }

    /** Zruší odběr (konkrétní endpoint prohlížeče). */
    public function unsubscribe(Request $request): Response
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:500']]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->noContent();
    }
}
