<?php

namespace App\Filament\Resources\Sablonas\Schemas;

use App\Models\Sablona;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SablonaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('typ')->label('Použití')->options(Sablona::TYPY)->default('zavada')->required(),
                TextInput::make('nazev')->label('Název (zobrazí se ve výběru)')->required(),
                Textarea::make('text')->label('Text šablony')->rows(4)->required()->columnSpanFull(),
                TextInput::make('poradi')->label('Pořadí')->numeric()->default(0),
                Toggle::make('aktivni')->label('Aktivní')->default(true),
            ]);
    }
}
