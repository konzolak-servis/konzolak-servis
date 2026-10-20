<?php

namespace App\Filament\Resources\SkladPolozkas\Schemas;

use App\Support\Platformy;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SkladPolozkaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('nazev')->label('Název')->required()->maxLength(255)->columnSpanFull(),
                        TextInput::make('kod')->label('Kód / označení')->maxLength(255),
                        Select::make('platforma')->label('Platforma')
                            ->options(Platformy::volby())
                            ->searchable()
                            ->helperText('Pro jakou konzoli / PC je díl určený.'),
                        TextInput::make('kategorie')->label('Typ dílu')
                            ->datalist(['HDMI IC', 'Laser', 'Ventilátor', 'Potenciometr', 'Konektor', 'SSD', 'HDD', 'Baterie', 'Kabeláž', 'Ostatní']),
                        TextInput::make('umisteni')->label('Umístění')->maxLength(255),
                    ]),
                Toggle::make('spotrebni')->label('Spotřební materiál (pasta, tekutý kov, ubrousky…)')
                    ->helperText('Bez evidence kusů: nakupuje se jako náklad firmy a nenaskladňuje se. Nenabízí se v zakázkách jako „Materiál ze skladu".')
                    ->live()->columnSpanFull(),
                Section::make('Zásoby a cena')
                    ->columns(3)
                    ->hidden(fn (Get $get) => (bool) $get('spotrebni'))
                    ->schema([
                        TextInput::make('mnozstvi_skladem')->label('Množství skladem')->numeric()->default(0)
                            ->disabled(fn (string $operation) => $operation === 'edit')
                            ->dehydrated(fn (string $operation) => $operation === 'create')
                            ->helperText(fn (string $operation) => $operation === 'edit'
                                ? 'Mění se jen nákupem, výdejem na zakázku nebo inventurou (tlačítko nahoře).'
                                : 'Počáteční stav – zapíše se do historie pohybů.'),
                        TextInput::make('min_mnozstvi')->label('Minimální množství')->numeric()->default(0),
                        TextInput::make('cena_ks_prumer')->label('Cena/ks (vážený průměr)')->numeric()->default(0)
                            ->suffix('Kč')
                            ->disabled(fn (string $operation) => $operation === 'edit')
                            ->dehydrated(fn (string $operation) => $operation === 'create')
                            ->helperText('Přepočítává se automaticky při naskladnění nákupu.'),
                    ]),
                Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
                Toggle::make('aktivni')->label('Aktivní')->default(true),
            ]);
    }
}
