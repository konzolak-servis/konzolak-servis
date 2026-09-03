<?php

namespace App\Filament\Resources\Nakups\RelationManagers;

use App\Models\SkladPolozka;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PolozkyRelationManager extends RelationManager
{
    protected static string $relationship = 'polozky';

    protected static ?string $title = 'Položky';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('sklad_polozka_id')
                    ->label('Skladová položka')
                    ->options(SkladPolozka::orderBy('nazev')->pluck('nazev', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn ($state, $set) => $state
                        ? $set('nazev', SkladPolozka::find($state)?->nazev)
                        : null)
                    ->helperText('Nech prázdné a vyplň název – nová položka se založí při naskladnění.'),
                TextInput::make('nazev')
                    ->label('Název')
                    ->required(fn (Get $get) => ! $get('sklad_polozka_id'))
                    ->maxLength(255),
                TextInput::make('mnozstvi_ks')->label('Počet ks')->numeric()->default(1)->required(),
                TextInput::make('castka_celkem')->label('Celková částka')->numeric()->default(0)->required()->suffix('Kč')
                    ->helperText('Cena za kus se dopočítá.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nazev')
            ->columns([
                TextColumn::make('nazev')->label('Název')->wrap(),
                TextColumn::make('mnozstvi_ks')->label('Ks')->numeric(),
                TextColumn::make('castka_celkem')->label('Celkem')->money('CZK'),
                TextColumn::make('cena_ks')->label('Cena/ks')->money('CZK'),
            ])
            ->headerActions([
                CreateAction::make()->label('Přidat položku')
                    ->visible(fn ($livewire) => ! $livewire->getOwnerRecord()->naskladneno),
            ])
            ->recordActions([
                EditAction::make()->visible(fn ($livewire) => ! $livewire->getOwnerRecord()->naskladneno),
                DeleteAction::make()->visible(fn ($livewire) => ! $livewire->getOwnerRecord()->naskladneno),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
