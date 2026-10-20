<?php

namespace App\Filament\Resources\KatalogZarizenis\Tables;

use App\Support\Platformy;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KatalogZarizenisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->select('katalog_zarizeni.*')->selectSub(
                \Illuminate\Support\Facades\DB::table('zarizeni')->selectRaw('count(*)')
                    ->whereRaw("zarizeni.oznaceni like concat(katalog_zarizeni.nazev, '%')"),
                'pouzito'))
            ->columns([
                TextInputColumn::make('poradi')
                    ->label('#')
                    ->type('number')
                    ->rules(['numeric'])
                    ->sortable()
                    ->width('1%'),
                TextColumn::make('kategorie')
                    ->label('Platforma')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Platformy::label($state))
                    ->sortable(),
                TextColumn::make('nazev')
                    ->label('Model')
                    ->searchable()->sortable(),
                TextColumn::make('pouzito')->label('Přijato ×')->sortable()->badge()->color('gray')
                    ->tooltip('Kolik zařízení tohoto modelu už bylo přijato – seřaď podle sloupce pro nejčastější.'),
                TextColumn::make('model_kod')
                    ->label('Kód modelu')
                    ->searchable(),
                ToggleColumn::make('aktivni')
                    ->label('Aktivní')
                    ->width('1%'),
            ])
            ->filters([
                SelectFilter::make('kategorie')->label('Platforma')->options(Platformy::HODNOTY),
            ])
            ->defaultSort('poradi')
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption('all')
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
