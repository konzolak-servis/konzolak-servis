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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ZakazkaForm
{
    /** Nabídka dopravců: běžní + ti, které jsi už u zakázek použil + aktuální hodnota. */
    private static function dopravci(?string $aktualni = null): array
    {
        $vychozi = ['Zásilkovna', 'Balíkovna', 'Česká pošta', 'PPL', 'DPD', 'GLS'];
        $pouzite = Zakazka::whereNotNull('dopravce')->where('dopravce', '!=', '')->distinct()->pluck('dopravce')->all();

        $out = [];
        foreach (array_merge($vychozi, $pouzite, array_filter([$aktualni])) as $d) {
            $out[$d] = $d;
        }

        return $out;
    }

    /** Počet zařízení v jedné zakázce (záložky Zařízení 1–5). */
    public const MAX_ZARIZENI = 5;

    /**
     * Záložky Zařízení 1–5, v každé šablona + textové pole. Zařízení 1 používá společné pole zakázky
     * ($pole1), zařízení 2–5 se ukládají k zařízení (pivot).
     */
    private static function zarizeniTabs(string $typ, string $label, string $sablonaTyp, string $pole1): Tabs
    {
        $tab = function (int $n) use ($typ, $label, $sablonaTyp, $pole1) {
            $pole = $n === 1 ? $pole1 : 'zarizeni_' . $n . '_' . $typ;

            return Tab::make('Zařízení ' . $n)->key('tab-' . $typ . '-' . $n)->schema([
                self::sablona($sablonaTyp, $pole, $typ . '_' . $n),
                Textarea::make($pole)
                    ->label($label . ' – zařízení ' . $n)
                    ->rows(3)
                    ->dehydrated($n === 1)
                    ->hintAction(self::ulozSablonu($sablonaTyp, $pole))
                    ->columnSpanFull(),
            ]);
        };

        return Tabs::make('zarizeni_tabs_' . $typ)->tabs(array_map($tab, range(1, self::MAX_ZARIZENI)));
    }

    /** Pole záložky dalšího zařízení: výběr zařízení + závada + provedená práce. */
    private static function dalsiZarizeniPole(int $n): array
    {
        return [
            self::dalsiZarizeniSelect($n),
        ];
    }

    /** Select dalšího zařízení (záložky 2–5); ukládá se do pivotu `zakazka_zarizeni`. */
    private static function dalsiZarizeniSelect(int $n): Select
    {
        return Select::make('zarizeni_' . $n)
            ->label('Zařízení ' . $n)
            ->options(fn (Get $get) => $get('zakaznik_id')
                ? Zarizeni::where('zakaznik_id', $get('zakaznik_id'))
                    ->get()->mapWithKeys(fn ($z) => [$z->id => $z->oznaceni . ($z->seriove_cislo ? " ({$z->seriove_cislo})" : '')])
                : [])
            ->getOptionLabelUsing(fn ($value) => ($z = Zarizeni::find($value)) ? $z->oznaceni . ($z->seriove_cislo ? " ({$z->seriove_cislo})" : '') : null)
            ->searchable()->preload()
            ->dehydrated(false)
            ->createOptionForm([
                Select::make('razeni')->label('Řazení modelů')
                                    ->options(['caste' => 'Nejčastější', 'poradi' => 'Podle katalogu', 'abeceda' => 'Abecedně'])
                                    ->default('caste')->native(false)->live()->dehydrated(false),
                                Select::make('model')->label('Model')
                    ->options(fn (Get $get) => \App\Models\KatalogZarizeni::volby($get('razeni') ?: 'caste'))
                    ->getSearchResultsUsing(fn (string $search, Get $get) => \App\Models\KatalogZarizeni::hledej($search, $get('razeni') ?: 'caste'))
                    ->searchable()->required(),
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
            ->columnSpanFull();
    }

    /** Do dat formuláře doplní zarizeni_2..5 z pivotu. */
    public static function naplnDalsi(array $data, Zakazka $z): array
    {
        foreach ($z->dalsiZarizeni()->get()->values() as $i => $zar) {
            $n = $i + 2;
            $data['zarizeni_' . $n] = $zar->id;
            $data['zarizeni_' . $n . '_zavada'] = $zar->pivot->zavada;
            $data['zarizeni_' . $n . '_pozadovane'] = $zar->pivot->pozadovane;
            $data['zarizeni_' . $n . '_prace'] = $zar->pivot->prace;
        }

        return $data;
    }

    /** Uloží zarizeni_2..5 z formuláře do pivotu (v pořadí záložek). */
    public static function ulozDalsi(Zakazka $z, array $stav): void
    {
        $ids = [];
        for ($n = 2; $n <= self::MAX_ZARIZENI; $n++) {
            $id = $stav['zarizeni_' . $n] ?? null;
            if ($id && (int) $id !== (int) $z->zarizeni_id && ! isset($ids[(int) $id])) {
                $ids[(int) $id] = [
                    'zavada' => trim((string) ($stav['zarizeni_' . $n . '_zavada'] ?? '')) ?: null,
                    'pozadovane' => trim((string) ($stav['zarizeni_' . $n . '_pozadovane'] ?? '')) ?: null,
                    'prace' => trim((string) ($stav['zarizeni_' . $n . '_prace'] ?? '')) ?: null,
                ];
            }
        }
        $z->dalsiZarizeni()->detach();
        foreach ($ids as $id => $pivot) {
            $z->dalsiZarizeni()->attach($id, $pivot);
        }
    }

    /** Rozbalovací výběr, který vloží text šablony do daného pole. */
    private static function sablona(string $typ, string $cilovePole, ?string $klic = null): Select
    {
        $nazev = '_sablona_' . ($klic ?? $typ);

        return Select::make($nazev)
            ->label('Vložit šablonu')
            ->options(fn () => Sablona::where('typ', $typ)->aktivni()->pluck('nazev', 'text'))
            ->searchable()
            ->dehydrated(false)
            ->live()
            ->afterStateUpdated(function ($state, Get $get, Set $set) use ($cilovePole, $nazev) {
                if (! $state) {
                    return;
                }
                $puvodni = trim((string) $get($cilovePole));
                $set($cilovePole, $puvodni === '' ? $state : $puvodni . "\n" . $state);
                $set($nazev, null);
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

                        Tabs::make('zarizeni_tabs')
                            ->tabs([
                                Tab::make('Zařízení 1')->key('tab-vyber-1')->schema([
                                Select::make('zarizeni_id')
                                    ->label('Zařízení 1')
                                    ->options(fn (Get $get) => $get('zakaznik_id')
                                        ? Zarizeni::where('zakaznik_id', $get('zakaznik_id'))
                                            ->get()->mapWithKeys(fn ($z) => [$z->id => $z->oznaceni . ($z->seriove_cislo ? " ({$z->seriove_cislo})" : '')])
                                        : [])
                                    ->searchable()
                                    ->createOptionForm([
                                        Select::make('razeni')->label('Řazení modelů')
                                    ->options(['caste' => 'Nejčastější', 'poradi' => 'Podle katalogu', 'abeceda' => 'Abecedně'])
                                    ->default('caste')->native(false)->live()->dehydrated(false),
                                Select::make('model')->label('Model')
                                            ->options(fn (Get $get) => \App\Models\KatalogZarizeni::volby($get('razeni') ?: 'caste'))
                                            ->getSearchResultsUsing(fn (string $search, Get $get) => \App\Models\KatalogZarizeni::hledej($search, $get('razeni') ?: 'caste'))
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
                                    ->getSelectedRecordUsing(fn ($state) => $state ? Zarizeni::find($state) : null)
                                    ->editOptionForm([
                                        Select::make('kategorie')->label('Kategorie')
                                            ->options(\App\Support\Platformy::HODNOTY)
                                            ->searchable()->required(),
                                        TextInput::make('oznaceni')->label('Označení zařízení')->required()
                                            ->placeholder('např. Xbox Series S – Xbox One S – bílá – model 1681'),
                                        TextInput::make('seriove_cislo')->label('Sériové číslo'),
                                    ])
                                    ->updateOptionUsing(function (array $data, Get $get) {
                                        Zarizeni::where('id', $get('zarizeni_id'))->update([
                                            'kategorie' => $data['kategorie'],
                                            'oznaceni' => $data['oznaceni'],
                                            'seriove_cislo' => $data['seriove_cislo'] ?? null,
                                        ]);
                                    })
                                    ->columnSpanFull(),
                                ]),
                                ...array_map(
                                    fn (int $n) => Tab::make('Zařízení ' . $n)->key('tab-vyber-' . $n)->schema(self::dalsiZarizeniPole($n)),
                                    range(2, 5),
                                ),
                            ])
                            ->columnSpan(2),
                        DatePicker::make('datum_prijeti')->label('Datum přijetí')->default(now())->native(false),
                        DatePicker::make('datum_vyrizeni')->label('Datum vyřízení')->native(false),
                        TextInput::make('predpokladana_cena')->label('Předpokládaná cena')->numeric()->suffix('Kč'),
                    ]),

                Section::make('Od zákazníka (servisní list)')
                    ->columns(1)
                    ->schema([
                        self::zarizeniTabs('zavada', 'Popis závady od zákazníka', 'zavada', 'popis_zavady'),
                        self::zarizeniTabs('pozadovane', 'Požadované řešení', 'reseni', 'pozadovane_reseni'),
                        Textarea::make('prislusenstvi')->label('Příslušenství')->rows(2),
                    ]),

                Section::make('Servis (protokol)')
                    ->columns(1)
                    ->schema([
                        Textarea::make('zjistena_zavada')->label('Zjištěná závada')->rows(3)
                            ->hintAction(self::ulozSablonu('zavada', 'zjistena_zavada')),
                        self::zarizeniTabs('prace', 'Návrh řešení a provedené práce', 'reseni', 'navrh_reseni_prace'),
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

                        TextInput::make('sleva_kod')->label('Slevový kód / kupon')
                            ->placeholder('např. UPLATNĚN KUPON ABC123')
                            ->maxLength(120),
                        \Filament\Forms\Components\Select::make('sleva_procent')->label('Sleva')
                            ->options(Zakazka::SLEVY)->default(0)->native(false)->live()
                            ->helperText('Odečte se automaticky od ceny opravy (bez dopravy).'),
                        \Filament\Forms\Components\Toggle::make('sleva_jen_prace')->label('Sleva jen na práci (nepočítat na materiál)')
                            ->helperText('Sleva se spočítá jen z účtovaných prací. Účtované díly (materiál) se neslevňují.')
                            ->visible(fn (Get $get) => (int) $get('sleva_procent') > 0),

                        \Filament\Forms\Components\Radio::make('zpusob_vydani')->label('Vyzvednutí')
                            ->options(Zakazka::ZPUSOBY_VYDANI)
                            ->default('osobne')
                            ->inline()
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                // Při přepnutí na odeslání předvyplň nejčastějšího dopravce, ať se nemusí vybírat pokaždé.
                                if ($state === 'odeslani' && blank($get('dopravce'))) {
                                    $set('dopravce', 'Zásilkovna');
                                }
                            })
                            ->columnSpanFull(),
                        DatePicker::make('odeslano_datum')->label('Odesláno dne')->native(false)
                            ->visible(fn (Get $get) => $get('zpusob_vydani') === 'odeslani'),
                        Select::make('dopravce')->label('Přes koho')
                            ->options(fn (Get $get) => self::dopravci($get('dopravce')))
                            ->getOptionLabelUsing(fn ($value) => $value)
                            ->native(false)->searchable()
                            ->placeholder('Vyber dopravce')
                            ->createOptionForm([
                                TextInput::make('nazev')->label('Název dopravce')->required()->maxLength(60),
                            ])
                            ->createOptionUsing(fn (array $data) => trim($data['nazev']))
                            ->visible(fn (Get $get) => $get('zpusob_vydani') === 'odeslani'),
                        TextInput::make('sledovaci_cislo')->label('Sledovací číslo')
                            ->visible(fn (Get $get) => $get('zpusob_vydani') === 'odeslani'),
                        \Filament\Forms\Components\Toggle::make('dobirka')->label('Na dobírku')
                            ->helperText('Zákazník platí dopravci při převzetí. Na dokladech nebude QR platba, ale „Hrazeno dobírkou".')
                            ->inline(false)
                            ->visible(fn (Get $get) => $get('zpusob_vydani') === 'odeslani'),
                        TextInput::make('postovne_naklad')->label('Poštovné, které platím já')->live(onBlur: true)
                            ->numeric()->suffix('Kč')
                            ->helperText('Zapíše se jako výdaj do peněžního deníku. Zákazníkovi se nepočítá, dokud ho nenaúčtuješ vpravo.')
                            ->visible(fn (Get $get) => $get('zpusob_vydani') === 'odeslani'),
                        TextInput::make('cena_dopravy')->label('Cena dopravy / dobírky')
                            ->numeric()->default(0)->suffix('Kč')
                            ->helperText('Připočte se k částce k úhradě zákazníka. 0 = zákazníkovi nic neúčtovat.')
                            ->hintAction(
                                PoleAkce::make('naucotovat_postovne')
                                    ->label('Naúčtovat zákazníkovi')
                                    ->icon('heroicon-m-arrow-down-on-square')
                                    ->visible(fn (Get $get) => (float) $get('postovne_naklad') > 0)
                                    ->action(fn (Get $get, Set $set) => $set('cena_dopravy', $get('postovne_naklad')))
                            )
                            ->visible(fn (Get $get) => $get('zpusob_vydani') === 'odeslani'),
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
