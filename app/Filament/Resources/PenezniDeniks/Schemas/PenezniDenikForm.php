<?php

namespace App\Filament\Resources\PenezniDeniks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PenezniDenikForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                DatePicker::make('datum')->label('Datum')->default(now())->native(false)->required(),
                Select::make('typ')->label('Typ')
                    ->options(['prijem' => 'Příjem', 'vydej' => 'Výdej'])->required(),
                TextInput::make('popis')->label('Popis')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('castka')->label('Částka')->numeric()->required()->suffix('Kč'),
                TextInput::make('kategorie')->label('Kategorie')
                    ->datalist(['Servis', 'Materiál', 'Vybavení', 'Ostatní']),
                TextInput::make('kde')->label('Kde (u výdeje)')->maxLength(255),
            ]);
    }
}
