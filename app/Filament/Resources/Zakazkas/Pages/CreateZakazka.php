<?php

namespace App\Filament\Resources\Zakazkas\Pages;

use App\Filament\Resources\Zakazkas\Schemas\ZakazkaForm;
use App\Filament\Resources\Zakazkas\ZakazkaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateZakazka extends CreateRecord
{
    protected static string $resource = ZakazkaResource::class;

    /** Doklad o převzetí se po vytvoření NEposílá automaticky – odešle se ručně tlačítkem „Odeslat servisní list". */
    protected function afterCreate(): void
    {
        ZakazkaForm::ulozDalsi($this->record, $this->form->getRawState());
    }
}
