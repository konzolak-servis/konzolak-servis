<?php

namespace App\Filament\Resources\Nakups\RelationManagers;

use App\Models\SkladPolozka;
use App\Models\Zakazka;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
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
                Toggle::make('neskladovat')
                    ->label('Neskladovat – jen provozní náklad')
                    ->helperText('Předplatné, software, doména, kancelář, spotřební materiál, vybavení '
                        . 'dílny… Nezaloží se skladová položka, jen se to započítá do částky nákupu '
                        . '(peněžní deník, kategorie „Náklady firmy").')
                    ->live()
                    ->columnSpanFull(),
                Select::make('sklad_polozka_id')
                    ->label('Skladová položka')
                    ->options(SkladPolozka::orderBy('nazev')->pluck('nazev', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn ($state, $set) => $state
                        ? $set('nazev', SkladPolozka::find($state)?->nazev)
                        : null)
                    ->helperText('Nech prázdné a vyplň název – nová položka se založí při naskladnění.')
                    ->visible(fn (Get $get) => ! $get('neskladovat')),
                TextInput::make('nazev')
                    ->label('Název')
                    ->required(fn (Get $get) => ! $get('sklad_polozka_id'))
                    ->maxLength(255)
                    ->columnSpan(fn (Get $get) => $get('neskladovat') ? 2 : 1),
                TextInput::make('mnozstvi_ks')->label('Počet ks')->numeric()->default(1)->required()
                    ->visible(fn (Get $get) => ! $get('neskladovat')),
                TextInput::make('castka_celkem')->label('Celková částka')->numeric()->default(0)->required()->suffix('Kč')
                    ->helperText(fn (Get $get) => $get('neskladovat') ? null : 'Cena za kus se dopočítá.'),
                Select::make('zakazka_id')
                    ->label('Pro zakázku (nepovinné)')
                    ->options(fn () => Zakazka::query()->orderByDesc('id')->limit(300)
                        ->get()->mapWithKeys(fn ($z) => [$z->id => $z->cislo . ' · ' . ($z->zakaznik?->nazev ?? '')])->all())
                    ->searchable()
                    ->preload()
                    ->helperText('Pro jakou opravu byl díl koupen. Zobrazí se v zakázce v přehledu nakoupených dílů.')
                    ->visible(fn (Get $get) => ! $get('neskladovat')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nazev')
            ->columns([
                IconColumn::make('neskladovat')->label('')->boolean()
                    ->trueIcon('heroicon-o-banknotes')->falseIcon('heroicon-o-cube')
                    ->trueColor('warning')->falseColor('gray')
                    ->tooltip(fn ($record) => $record->neskladovat ? 'Provozní náklad (neskladováno)' : 'Skladová položka'),
                TextColumn::make('nazev')->label('Název')->wrap(),
                TextColumn::make('mnozstvi_ks')->label('Ks')->numeric()
                    ->formatStateUsing(fn ($state, $record) => $record->neskladovat ? '—' : $state),
                TextColumn::make('castka_celkem')->label('Celkem')->money('CZK'),
                TextColumn::make('cena_ks')->label('Cena/ks')->money('CZK'),
                TextColumn::make('zakazka.cislo')->label('Pro zakázku')->badge()->placeholder('—'),
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
