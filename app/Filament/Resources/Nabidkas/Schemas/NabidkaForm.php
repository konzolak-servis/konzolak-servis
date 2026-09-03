<?php

namespace App\Filament\Resources\Nabidkas\Schemas;

use App\Models\Nabidka;
use App\Models\NabidkaPolozka;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class NabidkaForm
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
                        Select::make('stav')->label('Stav')->options(Nabidka::STAVY)->default('nova')->required(),
                        Select::make('zakaznik_id')
                            ->label('Zákazník')
                            ->relationship('zakaznik', 'jmeno')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->nazev)
                            ->searchable(['jmeno', 'firma_nazev'])->preload()->required()
                            ->createOptionForm(fn (Schema $s) => \App\Filament\Resources\Zakazniks\Schemas\ZakaznikForm::configure($s)->getComponents()),
                        DatePicker::make('datum')->label('Datum')->default(now())->native(false),
                        DatePicker::make('platnost_do')->label('Platnost do')->native(false),
                        TextInput::make('zaloha')->label('Záloha')->numeric()->default(0)->suffix('Kč'),
                    ]),

                Section::make('Komponenty a práce')
                    ->description('Přidej řádek pro každou komponentu i pro práci. Odkaz na e-shop a „můj náklad" se netisknou zákazníkovi.')
                    ->schema([
                        Repeater::make('polozky')
                            ->relationship()
                            ->label('')
                            ->addActionLabel('Přidat řádek')
                            ->reorderable()
                            ->collapsible()
                            ->live(onBlur: true)
                            ->itemLabel(fn (array $state) => trim(($state['skupina'] ?? '') . '  ' . ($state['popis'] ?? '')) ?: 'Nový řádek')
                            ->columns(2)
                            ->schema([
                                TextInput::make('skupina')->label('Komponenta')
                                    ->placeholder('Procesor, GPU, Práce…')
                                    ->datalist(['Základní deska', 'Procesor', 'Operační paměť', 'Úložiště', 'Grafická karta',
                                        'Zdroj', 'Skříň', 'Chlazení CPU', 'Operační systém', 'Práce', 'Příslušenství']),
                                TextInput::make('popis')->label('Model / popis')->required(),
                                Select::make('varianta')->label('Druh')
                                    ->options(NabidkaPolozka::VARIANTY)->default('nova')->required(),
                                TextInput::make('mnozstvi')->label('Počet ks')->numeric()->default(1)->live(onBlur: true),
                                TextInput::make('cena')->label('Cena za kus')->numeric()->required()->suffix('Kč')->live(onBlur: true),
                                TextInput::make('naklad_interni')->label('Můj náklad – skryté (nepovinné)')
                                    ->numeric()->suffix('Kč')->prefixIcon('heroicon-o-eye-slash')->live(onBlur: true),
                                TextInput::make('eshop_url')->label('Odkaz na e-shop – skryté (nepovinné)')
                                    ->url()->prefixIcon('heroicon-o-link')->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Interní přehled – nákup / prodej / zisk')
                    ->description('Vidíš jen ty, na dokladu se netiskne. Přepočítá se po opuštění pole.')
                    ->schema([
                        Placeholder::make('_souhrn')
                            ->label('')
                            ->content(function (Get $get): HtmlString {
                                $prodej = 0.0;
                                $naklad = 0.0;
                                foreach ((array) $get('polozky') as $p) {
                                    $ks = (float) (($p['mnozstvi'] ?? 1) ?: 1);
                                    $prodej += $ks * (float) ($p['cena'] ?? 0);
                                    $naklad += $ks * (float) ($p['naklad_interni'] ?? 0);
                                }
                                $zisk = $prodej - $naklad;

                                return new HtmlString(view('filament.partials.nabidka-souhrn', [
                                    'prodej' => $prodej,
                                    'naklad' => $naklad,
                                    'zisk' => $zisk,
                                    'marze' => $prodej > 0 ? round($zisk / $prodej * 100) : 0,
                                    'nakladPct' => $prodej > 0 ? round($naklad / $prodej * 100) : 0,
                                ])->render());
                            }),
                    ]),

                Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
            ]);
    }
}
