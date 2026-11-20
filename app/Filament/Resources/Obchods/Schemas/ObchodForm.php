<?php

namespace App\Filament\Resources\Obchods\Schemas;

use App\Models\Obchod;
use App\Support\Platformy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                            ->options(Platformy::volby())
                            ->searchable()
                            ->required(),
                        TextInput::make('nazev')
                            ->label('Označení')
                            ->placeholder('např. PS5 DualSense bílý / Xbox Series S / herní PC')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('seriove_cislo')->label('Sériové číslo'),
                        TextInput::make('cena')->label('Nákupní cena')->numeric()->required()->suffix('Kč'),
                        Radio::make('zpusob_uhrady')->label('Platba')
                            ->options(Obchod::ZPUSOBY_UHRADY)->default('hotove')->inline(),

                        Textarea::make('stav_popis')->label('Stav / příslušenství')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Prodej a zisk')
                    ->columns(3)
                    ->visible(fn (?Obchod $record) => $record !== null)
                    ->description('Bazar je interní – do peněžního deníku ani do oficiálních účetních dat se nic nezapisuje. Díly a náklady vkládej níže v „Investováno do zařízení“.')
                    ->schema([
                        Toggle::make('prodano')->label('Prodáno')->live(),
                        TextInput::make('prodejni_cena')->label('Prodejní cena')->numeric()->suffix('Kč')
                            ->required(fn (Get $get) => (bool) $get('prodano'))
                            ->visible(fn (Get $get) => (bool) $get('prodano')),
                        DatePicker::make('prodej_datum')->label('Datum prodeje')->native(false)
                            ->visible(fn (Get $get) => (bool) $get('prodano')),
                        TextInput::make('prodej_komu')->label('Komu')
                            ->visible(fn (Get $get) => (bool) $get('prodano')),
                        TextInput::make('prodej_kontakt')->label('Kontakt na kupujícího')
                            ->visible(fn (Get $get) => (bool) $get('prodano')),
                        Toggle::make('prodej_na_dalku')->label('Prodej na dálku (odesláno poštou)')
                            ->helperText('Na doklad se přidá poučení o právu odstoupit do 14 dnů.')
                            ->visible(fn (Get $get) => (bool) $get('prodano')),
                        Radio::make('prodej_zaruka_mesice')->label('Odpovědnost za vady')
                            ->options([12 => '12 měsíců (použité zboží)', 24 => '24 měsíců'])->default(12)->inline()
                            ->visible(fn (Get $get) => (bool) $get('prodano')),
                        Placeholder::make('naklady_celkem_info')
                            ->label('Investováno celkem')
                            ->content(fn (?Obchod $record) => $record
                                ? number_format($record->naklady_celkem, 0, ',', ' ').' Kč  (nákup '
                                    .number_format((float) $record->cena, 0, ',', ' ').' + díly '
                                    .number_format($record->naklady_dilu, 0, ',', ' ').')'
                                : '—'),
                        Placeholder::make('zisk_info')
                            ->label('Zisk po prodeji')
                            ->content(fn (?Obchod $record) => $record && $record->zisk !== null
                                ? number_format($record->zisk, 0, ',', ' ').' Kč'
                                : 'zatím neprodáno'),
                    ]),

                Section::make(fn (Get $get) => $get('typ') === 'prodej' ? 'Kupující' : 'Prodávající')
                    ->description(fn (Get $get) => $get('typ') === 'prodej' ? null
                        : ($get('vykup_zpusob') === 'na_dalku'
                            ? 'Nákup na dálku: podpis nejde – původ dolož inzerátem, komunikací a platbou na účet prodávajícího.'
                            : 'Osobní výkup: ověř občanku a údaje opiš (kopii nedělej). Prodávající podepíše doklad o výkupu.'))
                    ->columns(3)
                    ->schema([
                        Radio::make('vykup_zpusob')->label('Výkup')
                            ->options(Obchod::VYKUP_ZPUSOBY)->default('osobne')->inline()->live()
                            ->afterStateUpdated(fn ($state, $set) => $state === 'na_dalku' ? $set('zpusob_uhrady', 'ucet') : null)
                            ->visible(fn (Get $get) => $get('typ') === 'vykup')
                            ->columnSpanFull(),
                        TextInput::make('protistrana_jmeno')->label('Jméno a příjmení')
                            ->helperText(fn (Get $get) => $get('vykup_zpusob') === 'na_dalku' ? 'Jak je uvedeno u inzerátu / na účtu.' : null),
                        TextInput::make('protistrana_kontakt')->label('Telefon / e-mail'),
                        TextInput::make('protistrana_adresa')->label('Adresa')
                            ->visible(fn (Get $get) => $get('typ') === 'vykup'),

                        // osobně
                        \Filament\Forms\Components\DatePicker::make('protistrana_narozeni')->label('Datum narození')->native(false)
                            ->visible(fn (Get $get) => $get('typ') === 'vykup' && $get('vykup_zpusob') !== 'na_dalku'),
                        TextInput::make('protistrana_doklad')->label('Číslo občanského průkazu')
                            ->visible(fn (Get $get) => $get('typ') === 'vykup' && $get('vykup_zpusob') !== 'na_dalku'),

                        // na dálku
                        Select::make('inzerat_platforma')->label('Kde nakoupeno')
                            ->options(array_combine(Obchod::INZERCE, Obchod::INZERCE))
                            ->visible(fn (Get $get) => $get('typ') === 'vykup' && $get('vykup_zpusob') === 'na_dalku'),
                        TextInput::make('inzerat_url')->label('Odkaz na inzerát / profil')->url()->columnSpan(2)
                            ->visible(fn (Get $get) => $get('typ') === 'vykup' && $get('vykup_zpusob') === 'na_dalku'),
                        TextInput::make('protistrana_ucet')->label('Účet prodávajícího (kam jsem platil)')
                            ->visible(fn (Get $get) => $get('typ') === 'vykup' && $get('vykup_zpusob') === 'na_dalku'),
                        \Filament\Forms\Components\FileUpload::make('dukazy')->label('Důkazy: screenshot inzerátu, konverzace, potvrzení platby')
                            ->multiple()->panelLayout('grid')->columnSpan(2)
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->disk('public')->directory('bazar-doklady')->visibility('public')
                            ->maxFiles(20)->maxSize(10240)->openable()->downloadable()
                            ->visible(fn (Get $get) => $get('typ') === 'vykup' && $get('vykup_zpusob') === 'na_dalku'),

                        Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
