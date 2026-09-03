<?php

namespace App\Filament\Resources\CenikPolozkas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CenikPolozkasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kategorie')->label('Kategorie')->badge()->searchable()->sortable(),
                TextColumn::make('nazev')->label('Název úkonu')->searchable()->wrap(),
                TextColumn::make('cena')->label('Cena')->money('CZK')->sortable(),
                IconColumn::make('aktivni')->label('Aktivní')->boolean(),
            ])
            ->defaultSort('kategorie')
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
