<?php

namespace App\Filament\Resources\Nabidkas\RelationManagers;

use App\Models\NabidkaPolozka;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PolozkyRelationManager extends RelationManager
{
    protected static string $relationship = 'polozky';

    protected static ?string $title = 'Položky nabídky';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('skupina')->label('Komponenta / skupina')
                            ->placeholder('Základní deska, Procesor, Grafická karta, Práce…')
                            ->datalist(['Základní deska', 'Procesor', 'Operační paměť', 'Úložiště', 'Grafická karta',
                                'Zdroj', 'Skříň', 'Chlazení CPU', 'Operační systém', 'Práce', 'Příslušenství']),
                        TextInput::make('mnozstvi')->label('Množství')->numeric()->default(1)->required(),
                        TextInput::make('popis')->label('Konkrétní model / popis')->required()->maxLength(255)->columnSpanFull(),
                        TextInput::make('eshop_url')->label('Odkaz na e-shop (jen pro mě)')
                            ->url()->prefixIcon('heroicon-o-link')->columnSpanFull(),
                    ]),

                Section::make('Ceny')
                    ->columns(3)
                    ->schema([
                        Radio::make('varianta')->label('Nabízená varianta')
                            ->options(NabidkaPolozka::VARIANTY)->default('nova')->inline()->columnSpanFull(),
                        TextInput::make('cena_nova')->label('Cena – nový díl')->numeric()->suffix('Kč'),
                        TextInput::make('cena_bazar')->label('Cena – bazar')->numeric()->suffix('Kč'),
                        TextInput::make('naklad_interni')->label('Můj náklad (skryté, jen pro mě)')
                            ->numeric()->suffix('Kč')->prefixIcon('heroicon-o-eye-slash'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('popis')
            ->columns([
                TextColumn::make('skupina')->label('Komponenta')->badge(),
                TextColumn::make('popis')->label('Model')->wrap(),
                TextColumn::make('varianta')->label('Varianta')->badge()
                    ->formatStateUsing(fn ($state) => NabidkaPolozka::VARIANTY[$state] ?? $state)
                    ->color(fn ($state) => $state === 'bazar' ? 'warning' : 'success'),
                TextColumn::make('mnozstvi')->label('Ks')->numeric(),
                TextColumn::make('cena')->label('Cena/ks')->money('CZK'),
                TextColumn::make('cena_celkem')->label('Celkem')->money('CZK')
                    ->summarize(Sum::make()->label('Celkem nabídka')->money('CZK')),
                TextColumn::make('naklad_interni')->label('Můj náklad')->money('CZK')
                    ->color('gray')->toggleable()->toggledHiddenByDefault()
                    ->tooltip('Skryté – netiskne se zákazníkovi'),
                TextColumn::make('marze')->label('Marže')->money('CZK')
                    ->state(fn (NabidkaPolozka $record) => $record->marze)
                    ->color(fn ($state) => $state !== null && $state < 0 ? 'danger' : 'success')
                    ->toggleable()->toggledHiddenByDefault(),
                TextColumn::make('eshop_url')->label('E-shop')
                    ->formatStateUsing(fn ($state) => $state ? 'odkaz ↗' : '')
                    ->url(fn ($record) => $record->eshop_url)->openUrlInNewTab()
                    ->color('primary')->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Přidat komponentu'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
