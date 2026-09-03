<?php

namespace App\Filament\Resources\Zakazniks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ZakaznikForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('typ')
                            ->label('Typ')
                            ->options(['osoba' => 'Osoba', 'firma' => 'Firma'])
                            ->default('osoba')
                            ->required()
                            ->live(),
                        TextInput::make('jmeno')
                            ->label('Jméno a příjmení')
                            ->required(fn ($get) => $get('typ') === 'osoba')
                            ->maxLength(255),
                        TextInput::make('firma_nazev')
                            ->label('Název firmy')
                            ->visible(fn ($get) => $get('typ') === 'firma')
                            ->required(fn ($get) => $get('typ') === 'firma')
                            ->maxLength(255),
                        TextInput::make('ico')->label('IČO')->maxLength(20)
                            ->visible(fn ($get) => $get('typ') === 'firma'),
                        TextInput::make('dic')->label('DIČ')->maxLength(20)
                            ->visible(fn ($get) => $get('typ') === 'firma'),
                        TextInput::make('telefon')->label('Telefon')->tel()->maxLength(30),
                        TextInput::make('email')->label('E-mail')->email()->maxLength(255),
                    ]),
                Section::make('Adresa')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextInput::make('ulice')->label('Ulice a č. p.')->columnSpan(3),
                        TextInput::make('psc')->label('PSČ')->maxLength(10),
                        TextInput::make('mesto')->label('Město')->columnSpan(2),
                    ]),
                Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
            ]);
    }
}
