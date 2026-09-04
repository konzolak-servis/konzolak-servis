<?php

namespace App\Filament\Resources\Obchods\Schemas;

use App\Models\Obchod;
use App\Models\SkladPolozka;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ObchodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(3)
                    ->schema([
                        TextInput::make('cislo')->label('Číslo')->disabled()->dehydrated(false)
                            ->placeholder('přiřadí se automaticky'),
                        Select::make('typ')
                            ->label('Typ')
                            ->options(['vykup' => 'Výkup (kupuji od zákazníka)', 'prodej' => 'Prodej (prodávám zákazníkovi)'])
                            ->default(fn () => in_array(request()->query('typ'), ['vykup', 'prodej'], true)
                                ? request()->query('typ')
                                : 'vykup')
                            ->required()
                            ->live(),
                        DatePicker::make('datum')->label('Datum')->default(now())->native(false),

                        Select::make('kategorie')
                            ->label('Platforma / kategorie')
                            ->options(\App\Support\Platformy::volby())
                            ->searchable()
                            ->required(),
                        TextInput::make('nazev')
                            ->label('Označení')
                            ->placeholder('např. PS5 DualSense bílý / Xbox Series S / herní PC')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('seriove_cislo')->label('Sériové číslo'),
                        TextInput::make('cena')->label('Cena')->numeric()->required()->suffix('Kč'),
                        Radio::make('zpusob_uhrady')->label('Platba')
                            ->options(Obchod::ZPUSOBY_UHRADY)->default('hotove')->inline(),

                        Select::make('sklad_polozka_id')
                            ->label('Skladová položka (u prodeje)')
                            ->options(SkladPolozka::where('aktivni', true)->orderBy('nazev')
                                ->get()->mapWithKeys(fn ($s) => [$s->id => "{$s->nazev} (skladem {$s->mnozstvi_skladem})"]))
                            ->searchable()
                            ->visible(fn (Get $get) => $get('typ') === 'prodej')
                            ->helperText('Vyber položku, která se má odečíst ze skladu.'),

                        Textarea::make('stav_popis')->label('Stav / příslušenství')->rows(2)->columnSpanFull(),
                    ]),

                Section::make(fn (Get $get) => $get('typ') === 'prodej' ? 'Kupující' : 'Prodávající')
                    ->columns(3)
                    ->schema([
                        TextInput::make('protistrana_jmeno')->label('Jméno'),
                        TextInput::make('protistrana_kontakt')->label('Telefon / e-mail'),
                        TextInput::make('protistrana_doklad')
                            ->label('Číslo dokladu totožnosti')
                            ->helperText('U výkupu doporučeno kvůli evidenci původu zboží.')
                            ->visible(fn (Get $get) => $get('typ') === 'vykup'),
                        Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
