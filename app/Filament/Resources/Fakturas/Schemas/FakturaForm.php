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
                        TextInput::make('variabilni_symbol')->label('Variabilní symbol')->maxLength(10)
                            ->placeholder('automaticky podle zakázky / čísla faktury')
                            ->helperText('Při výběru zakázky se vyplní číslem zakázky.'),
                        Radio::make('forma_uhrady')->label('Forma úhrady')
                            ->options(['hotově' => 'Hotově', 'převodem' => 'Na účet'])
                            ->default('převodem')->inline()->required(),

                        TextInput::make('ico_odberatele')->label('IČO odběratele (ARES)')
                            ->placeholder('např. 45274649')->maxLength(12)
                            ->dehydrated(false)
                            ->helperText('Vyplň IČO firmy: najde se v zákaznících, nebo se založí z registru ARES a nastaví se jako odběratel.')
                            ->suffixAction(
                                \Filament\Actions\Action::make('ares_odberatel')
                                    ->label('Načíst z ARES')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->action(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::nastavOdberatele($get('ico_odberatele'), $set))
                            )
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                if (\App\Support\Ares::normalizuj($state) && strlen(preg_replace('/\D/', '', (string) $state)) >= 8) {
                                    self::nastavOdberatele($state, $set);
                                }
                            }),
                        Select::make('zakaznik_id')
                            ->label('Zákazník')
                            ->relationship('zakaznik', 'jmeno')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->nazev)
                            ->searchable(['jmeno', 'firma_nazev'])->preload()->required()
                            ->createOptionForm(fn (Schema $s) => \App\Filament\Resources\Zakazniks\Schemas\ZakaznikForm::configure($s)->getComponents()),
                        Select::make('zakazka_id')
                            ->label('Zakázka (nepovinné)')
                            ->relationship('zakazka', 'cislo')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) {
                                if (! $state || ! ($z = \App\Models\Zakazka::find($state))) {
                                    return;
                                }
                                // VS podle zakázky; zákazníka z zakázky jen když ještě žádný není vybraný
                                $set('variabilni_symbol', preg_replace('/\D/', '', $z->cislo));
                                if (blank($get('zakaznik_id'))) {
                                    $set('zakaznik_id', $z->zakaznik_id);
                                }
                            }),

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

    /** Najde firmu podle IČO (v zákaznících, jinak z ARES) a nastaví ji jako odběratele faktury. */
    public static function nastavOdberatele(?string $ico, \Filament\Schemas\Components\Utilities\Set $set): void
    {
        try {
            [$z, $novy] = \App\Models\Zakaznik::najdiNeboZalozPodleIco((string) $ico);
        } catch (\RuntimeException $e) {
            \Filament\Notifications\Notification::make()->title($e->getMessage())->warning()->send();

            return;
        }

        $set('zakaznik_id', $z->id);
        \Filament\Notifications\Notification::make()
            ->title('Odběratel: ' . $z->nazev)
            ->body($novy ? 'Firma byla nově založena z ARES (IČO' . ($z->dic ? ', DIČ' : '') . ' a adresa se vyplnily).' : 'Firma už v zákaznících byla.')
            ->success()->send();
    }
}
