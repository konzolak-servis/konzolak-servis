<?php

namespace App\Filament\Resources\KatalogZarizenis\Tables;

use App\Models\Zarizeni;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KatalogZarizenisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SelectColumn::make('kategorie')
                    ->label('Kategorie')
                    ->options(Zarizeni::KATEGORIE)
                    ->selectablePlaceholder(false)
                    ->rules(['required'])
                    ->width('1%'),
                TextInputColumn::make('nazev')
                    ->label('Model')
                    ->rules(['required', 'max:255'])
                    ->searchable(),
                TextInputColumn::make('model_kod')
                    ->label('Kód modelu')
                    ->searchable(),
                ToggleColumn::make('aktivni')
                    ->label('Aktivní')
                    ->width('1%'),
            ])
            ->filters([
                SelectFilter::make('kategorie')->label('Kategorie')->options(Zarizeni::KATEGORIE),
            ])
            ->defaultSort('poradi')
            ->reorderable('poradi')
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption('all')
            ->recordActions([
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
