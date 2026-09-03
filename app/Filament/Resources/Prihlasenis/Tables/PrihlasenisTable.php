<?php

namespace App\Filament\Resources\Prihlasenis\Tables;

use App\Models\Prihlaseni;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrihlasenisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Datum a čas')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                TextColumn::make('jmeno')
                    ->label('Uživatel')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                TextColumn::make('prohlizec')
                    ->label('Zařízení')
                    ->formatStateUsing(fn ($state, Prihlaseni $record) => $record->prohlizecZkraceny())
                    ->tooltip(fn (Prihlaseni $record) => $record->prohlizec),
                TextColumn::make('ip')
                    ->label('IP adresa')
                    ->copyable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('jmeno')
                    ->label('Uživatel')
                    ->options(fn () => Prihlaseni::query()->whereNotNull('jmeno')->distinct()
                        ->orderBy('jmeno')->pluck('jmeno', 'jmeno')->all()),
                Filter::make('dnes')
                    ->label('Jen dnes')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),
                Filter::make('tyden')
                    ->label('Posledních 7 dní')
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7))),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
