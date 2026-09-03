<?php

namespace App\Filament\Resources\SkladPolozkas\Pages;

use App\Filament\Resources\SkladPolozkas\SkladPolozkaResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSkladPolozkas extends ListRecords
{
    protected static string $resource = SkladPolozkaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_sklad')
                ->label('Export skladu (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('export.sklad'))
                ->openUrlInNewTab(),

            CreateAction::make(),
        ];
    }
}
