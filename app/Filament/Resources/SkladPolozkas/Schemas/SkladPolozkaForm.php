<?php

namespace App\Filament\Resources\SkladPolozkas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
                        TextInput::make('kategorie')->label('Kategorie')
                            ->datalist(['HDMI IC', 'Laser', 'Ventilátor', 'Potenciometr', 'SSD', 'HDD', 'Baterie', 'Ostatní']),
                        TextInput::make('umisteni')->label('Umístění')->maxLength(255),
                    ]),
                Section::make('Zásoby a cena')
                    ->columns(3)
                    ->schema([
                        TextInput::make('mnozstvi_skladem')->label('Množství skladem')->numeric()->default(0)
                            ->helperText('Mění se nákupy a výdejem na zakázky.'),
                        TextInput::make('min_mnozstvi')->label('Minimální množství')->numeric()->default(0),
                        TextInput::make('cena_ks_prumer')->label('Cena/ks (vážený průměr)')->numeric()->default(0)
                            ->suffix('Kč')->helperText('Přepočítává se automaticky při naskladnění nákupu.'),
                    ]),
                Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
                Toggle::make('aktivni')->label('Aktivní')->default(true),
            ]);
    }
}
