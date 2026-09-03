<?php

namespace App\Filament\Resources\PenezniDeniks\Tables;

use App\Models\PenezniDenik;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PenezniDeniksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('datum')->label('Datum')->date('d.m.Y')->sortable(),
                TextColumn::make('typ')->label('Typ')->badge()
                    ->formatStateUsing(fn ($state) => $state === 'prijem' ? 'Příjem' : 'Výdej')
                    ->color(fn ($state) => $state === 'prijem' ? 'success' : 'danger'),
                TextColumn::make('popis')->label('Popis')->searchable()->wrap(),
                TextColumn::make('doklad')->label('Doklad')
                    ->state(fn (PenezniDenik $record) => $record->doklad()[0] ?: '—')
                    ->url(fn (PenezniDenik $record) => $record->doklad()[1])
                    ->openUrlInNewTab()
                    ->color(fn (PenezniDenik $record) => $record->doklad()[1] ? 'primary' : 'gray')
                    ->weight('bold'),
                TextColumn::make('kategorie')->label('Kategorie')->badge()->toggleable(),
                TextColumn::make('zpusob')->label('Platba')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'hotove' => 'Hotově', 'ucet' => 'Na účet', default => '',
                    })->badge()->toggleable(),
                TextColumn::make('castka')->label('Částka')->money('CZK')->sortable()
                    ->summarize(Sum::make()->label('Součet')->money('CZK')),
            ])
            ->filters([
                SelectFilter::make('typ')->label('Typ')
                    ->options(['prijem' => 'Příjem', 'vydej' => 'Výdej']),
            ])
            ->defaultSort('datum', 'desc')
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
