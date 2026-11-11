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
                            ->maxLength(255)
                            ->datalist(fn () => \App\Models\Zakaznik::whereNotNull('jmeno')->orderBy('jmeno')->pluck('jmeno')->unique()->values()->all())
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($get, $set, $record) => self::doplnZeSeznamu($get, $set, $record)),
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
                        TextInput::make('telefon')->label('Telefon')->tel()->maxLength(30)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($get, $set, $record) => self::doplnZeSeznamu($get, $set, $record)),
                        TextInput::make('email')->label('E-mail')->email()->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($get, $set, $record) => self::doplnZeSeznamu($get, $set, $record)),
                        \Filament\Forms\Components\Placeholder::make('duplicita')
                            ->label('')
                            ->columnSpanFull()
                            ->visible(fn ($get, $record) => self::podobni($get, $record)->isNotEmpty())
                            ->content(fn ($get, $record) => new \Illuminate\Support\HtmlString(
                                '<div style="padding:.6rem .8rem;border-radius:.5rem;background:rgba(200,153,46,.15);color:#C8992E"><b>Tento zákazník už existuje:</b><br>'
                                . self::podobni($get, $record)->map(fn ($z) => e($z->vyber_label) . ' (' . $z->zakazky()->count() . ' zak.)')->implode('<br>')
                                . ($record ? '' : '<br><small>Uložením se použije existující zákazník (chybějící údaje se mu doplní).</small>')
                                . '</div>')),
                        \Filament\Forms\Components\Toggle::make('vytvorit_noveho')
                            ->label('Přesto založit nového zákazníka (jiný člověk se stejným jménem)')
                            ->columnSpanFull()
                            ->visible(fn ($get, $record) => ! $record && self::podobni($get, $record)->isNotEmpty()),
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

    private static function podobni($get, $record): \Illuminate\Support\Collection
    {
        return \App\Models\Zakaznik::podobni([
            'typ' => $get('typ'), 'jmeno' => $get('jmeno'), 'firma_nazev' => $get('firma_nazev'),
            'telefon' => $get('telefon'), 'email' => $get('email'),
        ], $record?->id);
    }

    /** U nového zákazníka: když odpovídá existujícímu, doplní prázdná pole jeho údaji. */
    private static function doplnZeSeznamu($get, $set, $record): void
    {
        if ($record || $get('vytvorit_noveho') || ! ($z = self::podobni($get, $record)->first())) {
            return;
        }
        foreach (['jmeno', 'telefon', 'email', 'ulice', 'psc', 'mesto'] as $pole) {
            if (blank($get($pole)) && filled($z->{$pole})) {
                $set($pole, $z->{$pole});
            }
        }
    }

    /** Výběr zákazníka (zakázka, faktura, nabídka): hledání v libovolném pořadí slov i podle telefonu, bez duplicit při zakládání. */
    public static function vyber(\Filament\Forms\Components\Select $select): \Filament\Forms\Components\Select
    {
        return $select
            ->getOptionLabelFromRecordUsing(fn ($record) => $record->vyber_label)
            ->getSearchResultsUsing(fn (string $search) => \App\Models\Zakaznik::hledej($search))
            ->createOptionForm(fn (Schema $s) => self::configure($s)->getComponents())
            ->createOptionUsing(function (array $data) {
                [$z, $novy] = \App\Models\Zakaznik::vytvorNeboNajdi($data);
                if (! $novy) {
                    \Filament\Notifications\Notification::make()->title('Vybrán existující zákazník: ' . $z->nazev)
                        ->body('Nový se nezaložil, chybějící údaje se doplnily.')->info()->send();
                }

                return $z->getKey();
            });
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
