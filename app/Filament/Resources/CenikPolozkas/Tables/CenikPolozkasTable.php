<?php

namespace App\Filament\Resources\CenikPolozkas\Tables;

use App\Support\Platformy;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CenikPolozkasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kategorie')->label('Platforma / kategorie')->badge()->sortable()
                    ->formatStateUsing(fn ($state) => Platformy::label($state)),
                TextColumn::make('nazev')->label('Název úkonu')->searchable()->wrap(),
                TextColumn::make('cena')->label('Cena')->money('CZK')->sortable(),
                IconColumn::make('aktivni')->label('Aktivní')->boolean(),
            ])
            ->filters([
                SelectFilter::make('kategorie')->label('Platforma / kategorie')->options(Platformy::HODNOTY),
            ])
            ->defaultSort('poradi')
            ->reorderable('poradi')
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption('all')
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
