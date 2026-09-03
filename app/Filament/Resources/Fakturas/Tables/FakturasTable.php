<?php

namespace App\Filament\Resources\Fakturas\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FakturasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cislo')->label('Číslo')->searchable()->sortable(),
                TextColumn::make('zakaznik.jmeno')->label('Zákazník')
                    ->getStateUsing(fn ($record) => $record->zakaznik?->nazev)
                    ->searchable(['zakaznici.jmeno', 'zakaznici.firma_nazev']),
                TextColumn::make('datum_vystaveni')->label('Vystaveno')->date('d.m.Y')->sortable(),
                TextColumn::make('datum_splatnosti')->label('Splatnost')->date('d.m.Y')->sortable(),
                TextColumn::make('celkem')->label('Celkem')->money('CZK')->sortable(),
                IconColumn::make('uhrazeno')->label('Uhrazeno')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('uhrazeno')->label('Uhrazeno'),
            ])
            ->defaultSort('datum_vystaveni', 'desc')
            ->recordActions([
                EditAction::make(),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('tisk.faktura', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
