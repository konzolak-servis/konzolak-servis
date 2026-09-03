<?php

namespace App\Filament\Resources\Zakazniks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ZakazniksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nazev')
                    ->label('Zákazník')
                    ->getStateUsing(fn ($record) => $record->nazev)
                    ->searchable(['jmeno', 'firma_nazev'])
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('jmeno', $direction)),
                TextColumn::make('typ')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'firma' ? 'Firma' : 'Osoba'),
                TextColumn::make('telefon')->label('Telefon')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable()->toggleable(),
                TextColumn::make('mesto')->label('Město')->searchable()->toggleable(),
                TextColumn::make('zakazky_count')->label('Zakázek')->counts('zakazky')->badge(),
                TextColumn::make('created_at')->label('Vytvořen')->date('d.m.Y')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('typ')->label('Typ')
                    ->options(['osoba' => 'Osoba', 'firma' => 'Firma']),
            ])
            ->defaultSort('jmeno')
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
