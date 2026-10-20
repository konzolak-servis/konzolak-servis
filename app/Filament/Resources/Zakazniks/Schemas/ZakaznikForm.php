<?php

namespace App\Filament\Resources\Zakazniks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ZakaznikForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('typ')
                            ->label('Typ')
                            ->options(['osoba' => 'Osoba', 'firma' => 'Firma'])
                            ->default('osoba')
                            ->required()
                            ->live(),
                        TextInput::make('jmeno')
                            ->label('Jméno a příjmení')
                            ->required(fn ($get) => $get('typ') === 'osoba')
                            ->maxLength(255),
                        TextInput::make('firma_nazev')
                            ->label('Název firmy')
                            ->visible(fn ($get) => $get('typ') === 'firma')
                            ->required(fn ($get) => $get('typ') === 'firma')
                            ->maxLength(255),
                        TextInput::make('ico')->label('IČO')->maxLength(20)
                            ->visible(fn ($get) => $get('typ') === 'firma')
                            ->helperText('Po zadání IČO se údaje firmy načtou z registru ARES.')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $get, $set) {
                                // Při opuštění pole se samo doplní, jen pokud je název firmy ještě prázdný.
                                if (blank($get('firma_nazev')) && \App\Support\Ares::normalizuj($state)) {
                                    self::nactiZAres($state, $set);
                                }
                            })
                            ->suffixAction(
                                \Filament\Actions\Action::make('ares')
                                    ->label('Načíst z ARES')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->action(fn ($get, $set) => self::nactiZAres($get('ico'), $set))
                            ),
                        TextInput::make('dic')->label('DIČ')->maxLength(20)
                            ->visible(fn ($get) => $get('typ') === 'firma'),
                        TextInput::make('telefon')->label('Telefon')->tel()->maxLength(30),
                        TextInput::make('email')->label('E-mail')->email()->maxLength(255),
                    ]),
                Section::make('Adresa')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextInput::make('ulice')->label('Ulice a č. p.')->columnSpan(3),
                        TextInput::make('psc')->label('PSČ')->maxLength(10),
                        TextInput::make('mesto')->label('Město')->columnSpan(2),
                    ]),
                Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
            ]);
    }

    /** Načte firmu z ARES do polí formuláře zákazníka. */
    public static function nactiZAres(?string $ico, $set): void
    {
        try {
            $a = \App\Support\Ares::najdi($ico);
        } catch (\RuntimeException $e) {
            \Filament\Notifications\Notification::make()->title($e->getMessage())->warning()->send();

            return;
        }

        $set('typ', 'firma');
        $set('ico', $a['ico']);
        $set('firma_nazev', $a['nazev']);
        $set('dic', $a['dic']);
        $set('ulice', $a['ulice']);
        $set('psc', $a['psc']);
        $set('mesto', $a['mesto']);

        \Filament\Notifications\Notification::make()->title('Načteno z ARES: ' . $a['nazev'])->success()->send();
    }
}
