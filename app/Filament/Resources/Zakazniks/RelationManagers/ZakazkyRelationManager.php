<?php

namespace App\Filament\Resources\Zakazniks\RelationManagers;

use App\Models\Zakazka;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;

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
                TextColumn::make('zaruka_do')
                    ->label('Záruka do')
                    ->state(fn (Zakazka $record) => $record->zarukaDo())
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->color(fn (Zakazka $record) => $record->vZaruce() ? 'success' : 'gray')
                    ->tooltip(fn (Zakazka $record) => $record->vZaruce() ? 'V záruce' : null)
                    ->toggleable(),
                TextColumn::make('cena_celkem')
                    ->label('Cena')
                    ->money('CZK')
                    ->summarize([
                        Summarizer::make('utraceno')
                            ->label('Utraceno celkem')
                            ->using(fn (Builder $query) => (float) (clone $query)
                                ->whereIn('stav', Zakazka::STAVY_ZAPLACENO)
                                ->sum('cena_celkem'))
                            ->money('CZK'),
                    ]),
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
