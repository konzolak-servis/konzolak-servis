<?php

namespace App\Filament\Resources\Zarizenis\Tables;

use App\Models\Zarizeni;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ZarizenisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('zakaznik.jmeno')
                    ->label('Zákazník')
                    ->getStateUsing(fn ($record) => $record->zakaznik?->nazev)
                    ->searchable(['zakaznici.jmeno', 'zakaznici.firma_nazev'])
                    ->url(fn ($record) => $record->zakaznik
                        ? \App\Filament\Resources\Zakazniks\ZakaznikResource::getUrl('edit', ['record' => $record->zakaznik_id])
                        : null),
                TextColumn::make('kategorie')->label('Kategorie')->badge()
                    ->formatStateUsing(fn ($state) => Zarizeni::KATEGORIE[$state] ?? $state),
                TextColumn::make('oznaceni')->label('Označení')->searchable(),
                TextColumn::make('seriove_cislo')->label('Sériové číslo')->searchable(),
                TextColumn::make('zakazky_count')->label('Oprav')->counts('zakazky')->badge(),
            ])
            ->filters([
                SelectFilter::make('kategorie')->label('Kategorie')->options(Zarizeni::KATEGORIE),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
