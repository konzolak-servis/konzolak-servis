<?php

namespace App\Filament\Resources\Nabidkas\Tables;

use App\Models\Nabidka;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NabidkasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cislo')->label('Číslo')->searchable()->sortable(),
                TextColumn::make('zakaznik.jmeno')->label('Zákazník')
                    ->getStateUsing(fn ($record) => $record->zakaznik?->nazev)
                    ->searchable(['zakaznici.jmeno', 'zakaznici.firma_nazev']),
                TextColumn::make('datum')->label('Datum')->date('d.m.Y')->sortable(),
                TextColumn::make('platnost_do')->label('Platnost do')->date('d.m.Y')->sortable(),
                TextColumn::make('celkem')->label('Celkem')->money('CZK')->sortable(),
                TextColumn::make('stav')->label('Stav')->badge()
                    ->formatStateUsing(fn ($state) => Nabidka::STAVY[$state] ?? $state),
            ])
            ->filters([
                SelectFilter::make('stav')->label('Stav')->options(Nabidka::STAVY),
            ])
            ->defaultSort('datum', 'desc')
            ->recordActions([
                EditAction::make(),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('tisk.nabidka', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
