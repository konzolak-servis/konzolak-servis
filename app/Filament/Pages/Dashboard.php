<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Fakturas\FakturaResource;
use App\Filament\Resources\Nabidkas\NabidkaResource;
use App\Filament\Resources\Obchods\ObchodResource;
use App\Filament\Resources\Zakazkas\ZakazkaResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('nova_zakazka')
                ->label('Nová zakázka')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->button()
                ->url(ZakazkaResource::getUrl('create')),

            ActionGroup::make([
                Action::make('novy_vykup')
                    ->label('Výkup zboží')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->url(ObchodResource::getUrl('create') . '?typ=vykup'),
                Action::make('novy_prodej')
                    ->label('Prodej zboží')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->url(ObchodResource::getUrl('create') . '?typ=prodej'),
                Action::make('nova_faktura')
                    ->label('Faktura')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->url(FakturaResource::getUrl('create')),
                Action::make('nova_nabidka')
                    ->label('Nabídka / PC sestava')
                    ->icon('heroicon-o-document-text')
                    ->url(NabidkaResource::getUrl('create')),
            ])
                ->label('Vytvořit další')
                ->icon('heroicon-o-plus-circle')
                ->button()
                ->color('gray'),
        ];
    }
}
