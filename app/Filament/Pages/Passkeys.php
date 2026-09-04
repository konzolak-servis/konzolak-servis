<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\JenProAdmina;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Passkeys extends Page
{
    use JenProAdmina;

    protected string $view = 'filament.pages.passkeys';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'Nastavení';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Přihlášení otiskem';

    protected static ?string $title = 'Přihlášení otiskem (passkey)';

    public function getViewData(): array
    {
        return [
            'klice' => auth()->user()->webAuthnCredentials()
                ->orderByDesc('created_at')
                ->get(['id', 'alias', 'disabled_at', 'created_at']),
        ];
    }

    public function smazat(string $id): void
    {
        $smazano = auth()->user()->webAuthnCredentials()->whereKey($id)->delete();

        if ($smazano) {
            Notification::make()->title('Passkey odebrán')->success()->send();
        }
    }
}
