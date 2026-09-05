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
                        DatePicker::make('datum')->label('Datum')->default(now())->native(false),
                        TextInput::make('dodavatel')->label('Dodavatel')
                            ->datalist(['Alza', 'Allegro', 'Konzoliste', 'Hadex', 'AliExpress', 'GM Electronic']),
                        TextInput::make('postovne')->label('Poštovné / doprava')->numeric()->default(0)->suffix('Kč')
                            ->helperText('Připočte se k výdaji v peněžním deníku, ale nerozpočítá se do skladové ceny kusů.'),
                        TextInput::make('celkem')->label('Celkem (položky + poštovné)')->numeric()->suffix('Kč')
                            ->disabled()->dehydrated(false)
                            ->helperText('Dopočítá se automaticky při naskladnění.'),
                        Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
                        Placeholder::make('info')
                            ->label('')
                            ->content('Položky (co a za kolik) přidej po uložení na záložce „Položky". Tlačítkem „Naskladnit" se kusy přidají na sklad v ceně za kus podle položky (bez poštovného), přepočítá se vážený průměr a do peněžního deníku se zapíše výdaj = položky + poštovné.')
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
