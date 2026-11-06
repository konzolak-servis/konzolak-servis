<?php

namespace App\Filament\Resources\Nakups\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NakupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('cislo')->label('Číslo')->disabled()->dehydrated(false)
                            ->placeholder('přiřadí se automaticky'),
                        DatePicker::make('datum')->label('Datum nákupu')->default(now())->native(false),
                        DatePicker::make('datum_naskladneni')->label('Naskladněno dne')->native(false)->disabled()->dehydrated(false)
                            ->helperText('Doplní se samo při naskladnění.'),
                        TextInput::make('dodavatel')->label('Dodavatel')
                            ->datalist(['Alza', 'Allegro', 'Konzoliste', 'Hadex', 'AliExpress', 'GM Electronic']),
                        TextInput::make('postovne')->label('Poštovné za balík (nepovinné)')->numeric()->default(0)->suffix('Kč')
                            ->helperText('Jen když máš u položek ceny bez dopravy – rozpočítá se do skladové ceny položek poměrem jejich ceny. Když zadáváš u položek rovnou zaplacenou částku, nech 0.'),
                        TextInput::make('celkem')->label('Celkem (položky + poštovné)')->numeric()->suffix('Kč')
                            ->disabled()->dehydrated(false)
                            ->helperText('Dopočítá se automaticky při naskladnění.'),
                        Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
                        Placeholder::make('info')
                            ->label('')
                            ->content('Položky (co a za kolik) přidej po uložení na záložce „Položky". Tlačítkem „Naskladnit" se kusy přidají na sklad – cena za kus = částka položky + její podíl z poštovného, přepočítá se vážený průměr a do peněžního deníku se zapíše výdaj = položky + poštovné. Naskladněný nákup jde opravit tlačítkem „Upravit nákup".')
                            ->columnSpanFull(),
                    ]),

                Section::make('Doklad o koupi')
                    ->icon('heroicon-o-paper-clip')
                    ->description('Sken nebo fotka účtenky / faktury od dodavatele – podklad pro daňovou evidenci.')
                    ->schema([
                        FileUpload::make('doklad_soubor')
                            ->label('Účtenka / faktura')
                            ->disk('public')
                            ->directory('nakupy')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                    ]),
            ]);
    }
}
