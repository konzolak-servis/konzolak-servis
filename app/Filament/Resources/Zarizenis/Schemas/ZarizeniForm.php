<?php

namespace App\Filament\Resources\Zarizenis\Schemas;

use App\Support\Platformy;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ZarizeniForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('zakaznik_id')
                    ->label('Zákazník')
                    ->relationship('zakaznik', 'jmeno')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->nazev)
                    ->searchable(['jmeno', 'firma_nazev'])
                    ->preload()
                    ->required(),
                Select::make('kategorie')
                    ->label('Platforma / kategorie')
                    ->options(Platformy::volby())
                    ->searchable(),
                TextInput::make('oznaceni')
                    ->label('Označení')
                    ->placeholder('např. PS4 Slim CUH-2116A')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('seriove_cislo')->label('Sériové číslo')->maxLength(255),
                Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
            ]);
    }
}
