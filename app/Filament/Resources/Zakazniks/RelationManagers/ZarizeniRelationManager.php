<?php

namespace App\Filament\Resources\Zakazniks\RelationManagers;

use App\Models\Zarizeni;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ZarizeniRelationManager extends RelationManager
{
    protected static string $relationship = 'zarizeni';

    protected static ?string $title = 'Zařízení';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('_katalog')
                    ->label('Vybrat z katalogu')
                    ->options(\App\Models\KatalogZarizeni::volby())
                    ->searchable()
                    ->dehydrated(false)
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $set('oznaceni', $state);
                            $set('kategorie', \App\Models\KatalogZarizeni::kategorieZNazvu($state));
                        }
                    })
                    ->helperText('Vyplní označení i kategorii. Nenajdeš? Napiš ručně.'),
                Select::make('kategorie')
                    ->label('Kategorie')
                    ->options(Zarizeni::KATEGORIE)
                    ->searchable(),
                TextInput::make('oznaceni')
                    ->label('Označení')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('seriove_cislo')->label('Sériové číslo')->maxLength(255),
                Textarea::make('poznamka')->label('Poznámka / upřesnění')->rows(2)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('oznaceni')
            ->columns([
                TextColumn::make('kategorie')->label('Kategorie')->badge(),
                TextColumn::make('oznaceni')->label('Označení')->searchable(),
                TextColumn::make('seriove_cislo')->label('Sériové číslo')->searchable(),
                TextColumn::make('zakazky_count')->label('Oprav')->counts('zakazky')->badge(),
            ])
            ->headerActions([
                CreateAction::make()->label('Přidat zařízení'),
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
