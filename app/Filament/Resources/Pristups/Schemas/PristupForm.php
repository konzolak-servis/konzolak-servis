<?php

namespace App\Filament\Resources\Pristups\Schemas;

use App\Models\Pristup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PristupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('nazev')->label('Název')->required()
                        ->placeholder('Wedos VPS · Doména konzolak.com · Seznam e-mail…'),
                    Select::make('kategorie')->label('Kategorie')->options(Pristup::KATEGORIE)->default('jine')->required(),
                    TextInput::make('url')->label('Adresa / přihlašovací URL')->url()->prefixIcon('heroicon-o-link'),
                    TextInput::make('uzivatel')->label('Uživatel / login')->prefixIcon('heroicon-o-user'),
                    TextInput::make('heslo')->label('Heslo')
                        ->password()->revealable()
                        ->prefixIcon('heroicon-o-lock-closed')
                        ->helperText('Ukládá se šifrovaně.')
                        ->columnSpanFull(),
                    Textarea::make('poznamka')->label('Poznámka (2FA, PIN, kontakt na podporu…)')->rows(3)->columnSpanFull(),
                ]),

            Section::make('Platnost a splatnost')
                ->columns(3)
                ->schema([
                    DatePicker::make('platnost_do')->label('Platnost / obnova / splatnost do')->native(false),
                    TextInput::make('pripominka_dni')->label('Připomenout (dní předem)')->numeric()->default(14),
                    TextInput::make('castka')->label('Poplatek (nepovinné)')->numeric()->suffix('Kč'),
                    Toggle::make('aktivni')->label('Aktivní')->default(true),
                ]),

            Section::make('Doklad')
                ->schema([
                    FileUpload::make('doklad_soubor')->label('Smlouva / faktura / doklad')
                        ->disk('public')->directory('pristupy')
                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                        ->maxSize(10240)->downloadable()->openable(),
                ]),
        ]);
    }
}
