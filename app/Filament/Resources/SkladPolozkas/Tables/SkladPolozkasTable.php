<?php

namespace App\Filament\Resources\SkladPolozkas\Tables;

use App\Support\Platformy;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SkladPolozkasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nazev')->label('Název')->searchable()->wrap(),
                TextColumn::make('platforma')->label('Platforma')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => Platformy::label($state))
                    ->placeholder('—')->toggleable(),
                TextColumn::make('kategorie')->label('Typ dílu')->badge()->searchable()->toggleable(),
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
                SelectFilter::make('platforma')->label('Platforma')->options(Platformy::HODNOTY),
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
