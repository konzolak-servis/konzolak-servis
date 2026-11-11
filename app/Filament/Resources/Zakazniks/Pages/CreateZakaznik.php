<?php

namespace App\Filament\Resources\Zakazniks\Pages;

use App\Filament\Resources\Zakazniks\ZakaznikResource;
use App\Models\Zakaznik;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateZakaznik extends CreateRecord
{
    protected static string $resource = ZakaznikResource::class;

    private bool $existujici = false;

    /** Když zákazník už existuje, nezakládá se znovu – otevře se existující (s doplněnými údaji). */
    protected function handleRecordCreation(array $data): Model
    {
        [$z, $novy] = Zakaznik::vytvorNeboNajdi($data);
        $this->existujici = ! $novy;

        return $z;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return $this->existujici
            ? Notification::make()->info()->title('Zákazník už existoval: ' . $this->record->nazev)
                ->body('Nový se nezaložil, otevřel se existující a doplnily se mu chybějící údaje.')
            : parent::getCreatedNotification();
    }

    protected function getRedirectUrl(): string
    {
        return ZakaznikResource::getUrl('edit', ['record' => $this->record]);
    }
}
