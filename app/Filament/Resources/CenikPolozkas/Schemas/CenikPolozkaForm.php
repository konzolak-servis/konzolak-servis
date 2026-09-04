<?php

namespace App\Filament\Resources\CenikPolozkas\Schemas;

use App\Support\Platformy;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CenikPolozkaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('kategorie')
                    ->label('Platforma / kategorie')
                    ->options(Platformy::volby())
                    ->searchable(),
                TextInput::make('nazev')->label('Název úkonu')->required()->maxLength(255),
                TextInput::make('cena')->label('Cena')->numeric()->default(0)->required()->suffix('Kč'),
                TextInput::make('poradi')->label('Pořadí')->numeric()->default(0),
                Toggle::make('aktivni')->label('Aktivní')->default(true),
            ]);
    }
}
