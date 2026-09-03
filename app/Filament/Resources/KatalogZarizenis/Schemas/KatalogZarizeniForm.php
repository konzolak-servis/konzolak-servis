<?php

namespace App\Filament\Resources\KatalogZarizenis\Schemas;

use App\Models\Zarizeni;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class KatalogZarizeniForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('kategorie')->label('Kategorie')->options(Zarizeni::KATEGORIE)->required(),
            TextInput::make('nazev')->label('Název modelu')->required()
                ->placeholder('např. PlayStation 5 Slim (disková)'),
            TextInput::make('model_kod')->label('Kód modelu (nepovinné)')->placeholder('CFI-20xx'),
            TextInput::make('poradi')->label('Pořadí')->numeric()->default(0),
            Toggle::make('aktivni')->label('Aktivní')->default(true),
        ]);
    }
}
