<?php

namespace App\Filament\Resources\Zakazkas\Pages;

use App\Filament\Resources\Zakazkas\ZakazkaResource;
use App\Support\ZakazkaMailer;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateZakazka extends CreateRecord
{
    protected static string $resource = ZakazkaResource::class;

    /** Po přijetí zakázky rovnou pošli zákazníkovi doklad o převzetí, pokud má uložený e-mail. */
    protected function afterCreate(): void
    {
        $z = $this->record;

        if (! $z->zakaznik?->email) {
            return;
        }

        try {
            $poslanoNa = ZakazkaMailer::posliDoklad($z);

            if ($poslanoNa) {
                Notification::make()
                    ->title('Doklad o převzetí odeslán na ' . $poslanoNa)
                    ->success()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Doklad o převzetí se nepodařilo odeslat e-mailem')
                ->body('Pošli ho ručně přes „Další" v zakázce. ' . $e->getMessage())
                ->warning()
                ->send();
        }
    }
}
