<?php

namespace App\Filament\Resources\Zakazkas\Schemas;

use App\Models\Sablona;
use App\Models\Zakazka;
use App\Models\Zakaznik;
use App\Models\Zarizeni;
use Filament\Actions\Action as PoleAkce;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ZakazkaForm
{
    /** Rozbalovací výběr, který vloží text šablony do daného pole. */
    private static function sablona(string $typ, string $cilovePole): Select
    {
        return Select::make('_sablona_' . $typ)
            ->label('Vložit šablonu')
            ->options(fn () => Sablona::where('typ', $typ)->aktivni()->pluck('nazev', 'text'))
            ->searchable()
            ->dehydrated(false)
            ->live()
            ->afterStateUpdated(function ($state, Get $get, Set $set) use ($cilovePole) {
                if (! $state) {
                    return;
                }
                $puvodni = trim((string) $get($cilovePole));
                $set($cilovePole, $puvodni === '' ? $state : $puvodni . "\n" . $state);
                $set('_sablona_' . explode('_', $cilovePole)[0], null);
            });
    }

    /** Akce v hintu pole – uloží aktuální text pole jako novou šablonu daného typu. */
    private static function ulozSablonu(string $typ, string $pole): PoleAkce
    {
        return PoleAkce::make('uloz_sablonu_' . $pole)
            ->label('Uložit jako šablonu')
            ->icon('heroicon-m-bookmark')
            ->visible(fn (Get $get) => filled(trim((string) $get($pole))))
            ->schema([
                TextInput::make('nazev')->label('Název šablony')->required()->maxLength(100)
                    ->helperText('Pod tímto názvem ji pak najdeš v „Vložit šablonu".'),
            ])
            ->action(function (array $data, Get $get) use ($typ, $pole) {
                Sablona::create([
                    'typ' => $typ,
                    'nazev' => $data['nazev'],
                    'text' => trim((string) $get($pole)),
                    'aktivni' => true,
                    'poradi' => (int) (Sablona::where('typ', $typ)->max('poradi') ?? 0) + 1,
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Šablona „' . $data['nazev'] . '" uložena')
                    ->success()->send();
            });
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Základ')
                    ->columns(3)
                    ->schema([
                        TextInput::make('cislo')->label('Číslo zakázky')->disabled()->dehydrated(false)
                            ->placeholder('přiřadí se automaticky'),
                        Select::make('stav')->label('Stav')->options(Zakazka::STAVY)->default('prijato')->required(),
                        TextInput::make('zaruka_mesice')->label('Záruka (měsíců)')->numeric()->default(3),

                        Placeholder::make('_reklamace')
                            ->label('')
                            ->visible(fn (?Zakazka $record) => $record?->reklamace_k_id)
                            ->content(fn (?Zakazka $record) => $record?->reklamaceK
                                ? '⚠ Reklamace zakázky ' . $record->reklamaceK->cislo
                                : '')
                            ->columnSpanFull(),

                        Select::make('zakaznik_id')
                            ->label('Zákazník')
                            ->relationship('zakaznik', 'jmeno')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->nazev)
                            ->searchable(['jmeno', 'firma_nazev'])
                            ->preload()
                            ->required()
                            ->live()
                            ->default(fn () => request()->query('zakaznik_id'))
                            ->createOptionForm(fn (Schema $s) => \App\Filament\Resources\Zakazniks\Schemas\ZakaznikForm::configure($s)->getComponents()),

                        Placeholder::make('kontakt_zakaznika')
                            ->label('Kontakt na zákazníka')
                            ->columnSpan(2)
                            ->content(function (Get $get) {
                                $z = $get('zakaznik_id') ? Zakaznik::find($get('zakaznik_id')) : null;
                                if (! $z) {
                                    return '—';
                                }
                                $casti = array_filter([
                                    $z->telefon ? '📞 ' . $z->telefon : null,
                                    $z->email ? '✉ ' . $z->email : null,
                                    $z->adresa_radek ?: null,
                                    $z->ico ? 'IČO ' . $z->ico : null,
                                ]);

                                return $casti ? implode('   ·   ', $casti) : 'Bez kontaktních údajů';
                            }),

                        Select::make('zarizeni_id')
                            ->label('Zařízení')
                            ->options(fn (Get $get) => $get('zakaznik_id')
                                ? Zarizeni::where('zakaznik_id', $get('zakaznik_id'))
                                    ->get()->mapWithKeys(fn ($z) => [$z->id => $z->oznaceni . ($z->seriove_cislo ? " ({$z->seriove_cislo})" : '')])
                                : [])
                            ->searchable()
                            ->createOptionForm([
                                Select::make('model')->label('Model')
                                    ->options(\App\Models\KatalogZarizeni::volby())
                                    ->searchable()->required()
                                    ->helperText('Nenašel jsi? Zvol „Jiné zařízení" a upřesni níže.'),
                                TextInput::make('oznaceni_doplnek')->label('Upřesnění (nepovinné)')
                                    ->placeholder('barva, revize desky, model. kód…'),
                                TextInput::make('seriove_cislo')->label('Sériové číslo'),
                            ])
                            ->createOptionUsing(function (array $data, Get $get) {
                                $nazev = trim($data['model']
                                    . (! empty($data['oznaceni_doplnek']) ? ' – ' . $data['oznaceni_doplnek'] : ''));

                                return Zarizeni::create([
                                    'zakaznik_id' => $get('zakaznik_id'),
                                    'kategorie' => \App\Models\KatalogZarizeni::kategorieZNazvu($data['model']),
                                    'oznaceni' => $nazev,
                                    'seriove_cislo' => $data['seriove_cislo'] ?? null,
                                ])->id;
                            })
                            ->columnSpan(2),

                        DatePicker::make('datum_prijeti')->label('Datum přijetí')->default(now())->native(false),
                        DatePicker::make('datum_vyrizeni')->label('Datum vyřízení')->native(false),
                        TextInput::make('predpokladana_cena')->label('Předpokládaná cena')->numeric()->suffix('Kč'),
                    ]),

                Section::make('Od zákazníka (servisní list)')
                    ->columns(1)
                    ->schema([
                        self::sablona('zavada', 'popis_zavady'),
                        Textarea::make('popis_zavady')->label('Popis závady')->rows(2)
                            ->hintAction(self::ulozSablonu('zavada', 'popis_zavady')),
                        Textarea::make('pozadovane_reseni')->label('Požadované řešení')->rows(2),
                        Textarea::make('prislusenstvi')->label('Příslušenství')->rows(2),
                    ]),

                Section::make('Servis (protokol)')
                    ->columns(1)
                    ->schema([
                        Textarea::make('zjistena_zavada')->label('Zjištěná závada')->rows(3)
                            ->hintAction(self::ulozSablonu('zavada', 'zjistena_zavada')),
                        self::sablona('reseni', 'navrh_reseni_prace'),
                        Textarea::make('navrh_reseni_prace')->label('Návrh řešení a provedené práce')->rows(3)
                            ->hintAction(self::ulozSablonu('reseni', 'navrh_reseni_prace')),
                        self::sablona('poznamka', 'poznamka'),
                        Textarea::make('poznamka')->label('Poznámka')->rows(2)
                            ->hintAction(self::ulozSablonu('poznamka', 'poznamka')),
                    ]),

                Section::make('Cena a platba')
                    ->columns(3)
                    ->schema([
                        TextInput::make('cena_celkem')
                            ->label('Cena celkem')
                            ->numeric()->suffix('Kč')->default(0)
                            ->helperText('Zadej ručně, nebo se spočítá z řádků na záložce „Položky" (pokud nějaké přidáš).'),
                        \Filament\Forms\Components\Radio::make('zpusob_uhrady')->label('Způsob platby')
                            ->options(Zakazka::ZPUSOBY_UHRADY)
                            ->default('hotove')
                            ->inline(),
                        TextInput::make('zaloha')->label('Záloha')->numeric()->default(0)->suffix('Kč')->live(),
                        DatePicker::make('zaloha_datum')->label('Záloha přijata dne')->native(false)
                            ->visible(fn (Get $get) => (float) $get('zaloha') > 0),
                        Toggle::make('zaloha_v_prijmech')
                            ->label('Zálohu zapsat do příjmů k tomuto datu')
                            ->helperText('Vhodné u hotovosti přijaté v jiném roce než dokončení.')
                            ->visible(fn (Get $get) => (float) $get('zaloha') > 0),
                    ]),

                Section::make('Fotodokumentace')
                    ->icon('heroicon-o-camera')
                    ->collapsible()
                    ->description('Fotky stavu zařízení při příjmu (poškození, příslušenství).')
                    ->schema([
                        FileUpload::make('fotky')
                            ->label('Fotky')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->disk('public')
                            ->directory('fotky')
                            ->maxSize(10240)
                            ->openable()
                            ->downloadable()
                            ->panelLayout('grid'),
                    ]),

                Section::make('Interní – jen pro mě (netiskne se na doklady)')
                    ->icon('heroicon-o-lock-closed')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Textarea::make('interni_hotovo')->label('Co je uděláno')->rows(3),
                        Textarea::make('interni_potreba')->label('Co je potřeba')->rows(3),
                        Toggle::make('dil_objednany')->label('Náhradní díl objednán')->inline(false),
                        TextInput::make('dil_info')->label('Jaký díl / kde / kdy objednáno'),
                    ]),

                Section::make('Podepsaný doklad')
                    ->icon('heroicon-o-paper-clip')
                    ->collapsible()
                    ->description('Sken nebo fotka dokladu podepsaného zákazníkem při převzetí (PDF nebo obrázek).')
                    ->schema([
                        FileUpload::make('sken_dokladu')
                            ->label('Sken / fotka')
                            ->disk('public')
                            ->directory('skeny')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                    ]),
            ]);
    }
}
