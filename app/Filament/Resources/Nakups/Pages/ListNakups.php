<?php

namespace App\Filament\Resources\Nakups\Pages;

use App\Filament\Resources\Nakups\NakupResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListNakups extends ListRecords
{
    protected static string $resource = NakupResource::class;

    protected function getHeaderActions(): array
    {
        $roky = collect(range((int) now()->year, (int) now()->year - 5))
            ->mapWithKeys(fn ($r) => [$r => (string) $r])->all();

        return [
            Action::make('export_naklady')
                ->label('Náklady za rok (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->schema([Select::make('rok')->label('Rok')->options($roky)->default((int) now()->year)])
                ->action(fn (array $data) => redirect()->route('export.naklady', $data['rok'])),

            CreateAction::make(),
        ];
    }
}
