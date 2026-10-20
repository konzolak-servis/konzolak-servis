<?php

namespace App\Filament\Resources\SkladPolozkas\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PohybyRelationManager extends RelationManager
{
    protected static string $relationship = 'pohyby';

    protected static ?string $title = 'Historie pohybů';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $typy = ['prijem' => 'Příjem', 'vydej' => 'Výdej', 'korekce' => 'Korekce / inventura'];

        return $table
            ->recordTitleAttribute('typ')
            ->columns([
                TextColumn::make('datum')->label('Datum')->date('d.m.Y')->sortable(),
                TextColumn::make('typ')->label('Pohyb')->badge()
                    ->formatStateUsing(fn ($state) => $typy[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'prijem' => 'success', 'vydej' => 'danger', default => 'warning' }),
                TextColumn::make('mnozstvi')->label('Množství')
                    ->formatStateUsing(fn ($state, $record) => ($record->typ === 'vydej' ? '−' : ((float) $state < 0 ? '' : '+'))
                        . rtrim(rtrim(number_format((float) $state, 3, ',', ' '), '0'), ',')),
                TextColumn::make('cena_ks')->label('Cena/ks')->money('CZK'),
                TextColumn::make('nakup.cislo')->label('Nákup')->placeholder('—'),
                TextColumn::make('zakazka.cislo')->label('Zakázka')->placeholder('—'),
                TextColumn::make('poznamka')->label('Poznámka')->wrap()->placeholder('—'),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50]);
    }
}
