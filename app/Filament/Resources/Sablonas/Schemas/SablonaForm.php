<?php

namespace App\Filament\Resources\Sablonas\Schemas;

use App\Models\Sablona;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SablonaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('typ')->label('Použití')->options(Sablona::TYPY)->default('zavada')->required()->live(),
                TextInput::make('nazev')->label('Název (zobrazí se ve výběru)')->required(),
                Textarea::make('text')->label('Text šablony')->rows(4)->required()->columnSpanFull()
                    ->helperText(fn (Get $get) => $get('typ') === 'whatsapp'
                        ? 'Značky se doplní automaticky: {cislo} {zarizeni} {zakaznik} {cena} {doplatek} {zaloha} {stav} {tel} {firma} {odkaz}'
                        : null),
                TextInput::make('poradi')->label('Pořadí')->numeric()->default(0),
                Toggle::make('aktivni')->label('Aktivní')->default(true),
            ]);
    }
}
