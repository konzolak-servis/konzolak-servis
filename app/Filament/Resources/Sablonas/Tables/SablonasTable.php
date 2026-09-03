<?php

namespace App\Filament\Resources\Sablonas\Tables;

use App\Models\Sablona;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SablonasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('typ')->label('Použití')->badge()
                    ->formatStateUsing(fn ($s) => Sablona::TYPY[$s] ?? $s)->sortable(),
                TextColumn::make('nazev')->label('Název')->searchable(),
                TextColumn::make('text')->label('Text')->limit(80)->wrap(),
                IconColumn::make('aktivni')->label('Aktivní')->boolean(),
            ])
            ->filters([
                SelectFilter::make('typ')->label('Použití')->options(Sablona::TYPY),
            ])
            ->defaultSort('typ')
            ->reorderable('poradi')
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
