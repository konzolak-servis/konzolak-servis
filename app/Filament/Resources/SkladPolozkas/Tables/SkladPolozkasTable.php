<?php

namespace App\Filament\Resources\SkladPolozkas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class SkladPolozkasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nazev')->label('Název')->searchable()->wrap(),
                TextColumn::make('kategorie')->label('Kategorie')->badge()->searchable()->toggleable(),
                TextColumn::make('mnozstvi_skladem')
                    ->label('Skladem')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->pod_minimem ? 'danger' : 'success'),
                TextColumn::make('min_mnozstvi')->label('Min.')->numeric()->toggleable(),
                TextColumn::make('cena_ks_prumer')->label('Cena/ks Ø')->money('CZK')->sortable(),
                TextColumn::make('umisteni')->label('Umístění')->searchable()->toggleable(),
            ])
            ->filters([
                Filter::make('pod_minimem')
                    ->label('Jen pod minimem')
                    ->query(fn ($query) => $query
                        ->whereColumn('mnozstvi_skladem', '<=', 'min_mnozstvi')
                        ->where('min_mnozstvi', '>', 0)),
            ])
            ->defaultSort('nazev')
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
