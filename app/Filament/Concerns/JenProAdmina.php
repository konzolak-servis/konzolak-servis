<?php

namespace App\Filament\Concerns;

/**
 * Filament stránka / resource dostupná jen administrátorovi.
 * Skryje položku z navigace i zamezí přímý přístup na URL.
 */
trait JenProAdmina
{
    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->jeAdmin());
    }
}
