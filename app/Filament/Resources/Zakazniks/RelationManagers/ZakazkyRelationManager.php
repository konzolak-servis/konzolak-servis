<?php

namespace App\Filament\Resources\Zakazniks\RelationManagers;

use App\Models\Zakazka;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ZakazkyRelationManager extends RelationManager
{
    protected static string $relationship = 'zakazky';

    protected static ?string $title = 'Zakázky';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cislo')
            ->columns([
                TextColumn::make('cislo')->label('Číslo')->searchable(),
                TextColumn::make('zarizeni.oznaceni')->label('Zařízení'),
                TextColumn::make('stav')->label('Stav')->badge()
                    ->formatStateUsing(fn ($state) => Zakazka::STAVY[$state] ?? $state),
                TextColumn::make('datum_prijeti')->label('Přijato')->date('d.m.Y'),
                TextColumn::make('cena_celkem')->label('Cena')->money('CZK'),
            ])
            ->defaultSort('datum_prijeti', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Nová zakázka')
                    ->url(fn ($livewire) => \App\Filament\Resources\Zakazkas\ZakazkaResource::getUrl('create', [
                        'zakaznik_id' => $livewire->getOwnerRecord()->getKey(),
                    ])),
            ])
            ->recordUrl(fn ($record) => \App\Filament\Resources\Zakazkas\ZakazkaResource::getUrl('edit', ['record' => $record]));
    }
}
