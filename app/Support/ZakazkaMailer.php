<?php

namespace App\Support;

use App\Http\Controllers\TiskController;
use App\Mail\DokladZakazky;
use App\Models\Zakazka;
use Illuminate\Support\Facades\Mail;

/**
 * Sdílená logika odesílání dokladů k zakázce e-mailem – používá jak EditZakazka
 * (ruční tlačítko), tak CreateZakazka (automaticky při přijetí).
 */
class ZakazkaMailer
{
    /** Pošle doklad o převzetí. Vrátí e-mail, na který se poslalo, nebo null (chybí adresa). */
    public static function posliDoklad(Zakazka $z, ?string $email = null): ?string
    {
        $email = $email ?: $z->zakaznik?->email;

        if (! $email) {
            return null;
        }

        $pdf = (new TiskController)->servisniDoklad($z)->getContent();
        Mail::to($email)->send(new DokladZakazky($z, $pdf));

        return $email;
    }
}
