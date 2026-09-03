<?php

namespace App\Filament\Resources\Fakturas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FakturaForm
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
                        TextInput::make('variabilni_symbol')->label('Variabilní symbol')->disabled()->dehydrated(false),
                        Radio::make('forma_uhrady')->label('Forma úhrady')
                            ->options(['hotově' => 'Hotově', 'převodem' => 'Na účet'])
                            ->default('převodem')->inline()->required(),

                        Select::make('zakaznik_id')
                            ->label('Zákazník')
                            ->relationship('zakaznik', 'jmeno')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->nazev)
                            ->searchable(['jmeno', 'firma_nazev'])->preload()->required()
                            ->createOptionForm(fn (Schema $s) => \App\Filament\Resources\Zakazniks\Schemas\ZakaznikForm::configure($s)->getComponents()),
                        Select::make('zakazka_id')
                            ->label('Zakázka (nepovinné)')
                            ->relationship('zakazka', 'cislo')
                            ->searchable(),

                        DatePicker::make('datum_vystaveni')->label('Datum vystavení')->default(now())->native(false),
                        DatePicker::make('datum_splatnosti')->label('Datum splatnosti')->native(false),

                        Toggle::make('uhrazeno')->label('Uhrazeno')
                            ->helperText('Po zaškrtnutí se přidá příjem do peněžního deníku.'),
                        DatePicker::make('datum_uhrady')->label('Datum úhrady')->native(false),
                    ]),

                Section::make('Řádky faktury')
                    ->schema([
                        Repeater::make('polozky')
                            ->relationship()
                            ->label('')
                            ->addActionLabel('Přidat řádek')
                            ->reorderable()
                            ->columns(2)
                            ->itemLabel(fn (array $state) => ($state['popis'] ?? '') ?: 'Nový řádek')
                            ->schema([
                                TextInput::make('zarizeni_text')->label('Zařízení (nepovinné)'),
                                TextInput::make('popis')->label('Popis práce')->required(),
                                TextInput::make('mnozstvi')->label('Počet ks')->numeric()->default(1),
                                TextInput::make('cena')->label('Cena za kus')->numeric()->required()->suffix('Kč'),
                            ]),
                    ]),

                Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
            ]);
    }
}
