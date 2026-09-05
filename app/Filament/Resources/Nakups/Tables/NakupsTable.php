<?php

namespace App\Filament\Resources\Nakups\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NakupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cislo')->label('Číslo')->searchable()->sortable(),
                TextColumn::make('datum')->label('Datum')->date('d.m.Y')->sortable(),
                TextColumn::make('dodavatel')->label('Dodavatel')->badge()->searchable(),
                TextColumn::make('polozky_count')->label('Položek')->counts('polozky'),
                TextColumn::make('postovne')->label('Poštovné')->money('CZK')->toggleable()->placeholder('—'),
                TextColumn::make('celkem')->label('Celkem')->money('CZK')->sortable(),
                IconColumn::make('doklad_soubor')->label('Doklad')
                    ->state(fn ($record) => filled($record->doklad_soubor))
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')->falseIcon('heroicon-o-minus')
                    ->trueColor('success')->falseColor('gray'),
                IconColumn::make('naskladneno')->label('Naskladněno')->boolean(),
            ])
            ->defaultSort('datum', 'desc')
            ->recordActions([
                Action::make('naskladnit')
                    ->label('Naskladnit')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->naskladneno && $record->polozky()->exists())
                    ->requiresConfirmation()
                    ->modalDescription('Přidá kusy na sklad v ceně podle položky (bez poštovného), přepočítá vážený průměr a do peněžního deníku zapíše výdaj = položky + poštovné. Nelze vzít zpět.')
                    ->action(fn ($record) => $record->naskladnit()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
