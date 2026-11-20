<?php

namespace App\Filament\Resources\Zapujckas\Schemas;

use App\Filament\Resources\Zakazniks\Schemas\ZakaznikForm;
use App\Models\Zakazka;
use App\Models\Zapujcka;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ZapujckaForm
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
                        Select::make('stav')->label('Stav')->options(Zapujcka::STAVY)->default('zapujceno')
                            ->disabled()->dehydrated(false)
                            ->helperText('Vrácení se zadává tlačítkem „Vrátit zápůjčku".'),
                        Select::make('zakazka_id')->label('K zakázce (nepovinné)')
                            ->options(fn () => Zakazka::with('zakaznik')->orderByDesc('id')->limit(300)->get()
                                ->mapWithKeys(fn ($z) => [$z->id => $z->cislo . ' · ' . ($z->zakaznik?->nazev ?? '')])->all())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, $set) {
                                if ($state && blank($get('zakaznik_id')) && ($z = Zakazka::find($state))) {
                                    $set('zakaznik_id', $z->zakaznik_id);
                                }
                                // zápůjčka k opravě = zdarma po dobu opravy
                                $set('zdarma', (bool) $state);
                            })
                            ->helperText('Např. náhradní zařízení po dobu opravy.'),

                        ZakaznikForm::vyber(Select::make('zakaznik_id')
                            ->label('Zákazník')
                            ->relationship('zakaznik', 'jmeno')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->vyber_label)
                            ->searchable()->preload()->required())
                            ->columnSpan(2),
                        DatePicker::make('datum_zapujceni')->label('Zapůjčeno dne')->default(now())->native(false)->required(),
                        TextInput::make('doklad_totoznosti')->label('Doklad totožnosti (č. OP)')->maxLength(50)
                            ->placeholder('např. OP 123456789')
                            ->helperText('Ověř podle dokladu při převzetí – uvede se v protokolu.'),

                        DatePicker::make('vratit_do')->label('Vrátit do')->native(false),
                        TextInput::make('kauce')->label('Kauce (vratná záloha)')->numeric()->default(0)->suffix('Kč')
                            ->helperText('Vrací se při vrácení zařízení – není to příjem.'),
                        Radio::make('kauce_forma')->label('Kauce přijata')
                            ->options(['hotově' => 'Hotově', 'převodem' => 'Převodem'])->default('hotově')->inline(),

                        \Filament\Forms\Components\Toggle::make('zdarma')->label('Zdarma po dobu opravy')
                            ->inline(false)->live()
                            ->helperText('Za zapůjčení se nic neúčtuje (zapne se samo při výběru zakázky).'),
                        TextInput::make('denni_sazba')->label('Cena za den (Po–Ne)')->numeric()->default(0)->suffix('Kč / den')
                            ->disabled(fn (Get $get) => (bool) $get('zdarma'))
                            ->helperText('Za všechna zařízení dohromady. Při vrácení se spočítá podle dnů a strhne z kauce.'),
                        TextInput::make('vikend_cely')->label('Celý víkend (Pá–Ne)')->numeric()->suffix('Kč')
                            ->disabled(fn (Get $get) => (bool) $get('zdarma'))
                            ->helperText('Pá+So+Ne v zápůjčce se účtuje touto cenou, pokud je levnější.'),

                        Textarea::make('poznamka')->label('Podmínky / poznámka')->rows(2)->columnSpanFull()
                            ->placeholder('např. zapůjčeno po dobu opravy zakázky, bez her…'),
                    ]),

                Section::make('Zapůjčená zařízení')
                    ->schema([
                        Repeater::make('polozky')
                            ->relationship()
                            ->label('')
                            ->orderColumn('poradi')
                            ->addActionLabel('Přidat zařízení')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(6)
                            ->itemLabel(fn (array $state) => ($state['nazev'] ?? '') ?: 'Zařízení')
                            ->schema([
                                Select::make('zapujcni_zarizeni_id')->label('Ze seznamu zařízení k zápůjčce')
                                    ->columnSpanFull()
                                    ->options(fn ($state) => \App\Models\ZapujcniZarizeni::volne($state ? (int) $state : null)
                                        ->orderBy('nazev')->get()->mapWithKeys(fn ($d) => [$d->id => $d->popisek])->all())
                                    ->searchable()
                                    ->live()
                                    ->placeholder('vyber volné zařízení, nebo vyplň ručně níže')
                                    ->afterStateUpdated(function ($state, $set, Get $get) {
                                        if (! $state || ! ($d = \App\Models\ZapujcniZarizeni::find($state))) {
                                            return;
                                        }
                                        $set('nazev', $d->nazev);
                                        $set('seriove_cislo', $d->seriove_cislo);
                                        $set('prislusenstvi', $d->prislusenstvi);
                                        $set('stav_pri_zapujceni', $d->stav_popis);
                                        $set('hodnota', $d->hodnota);
                                        // doporučená kauce = součet kaucí vybraných zařízení (jen když ji ještě nikdo nezměnil ručně)
                                        $ids = collect($get('../../polozky'))->pluck('zapujcni_zarizeni_id')->filter()->push($state)->unique();
                                        $soucet = (float) \App\Models\ZapujcniZarizeni::whereKey($ids)->sum('kauce');
                                        if ($soucet > 0 && (float) $get('../../kauce') < $soucet) {
                                            $set('../../kauce', $soucet);
                                        }
                                        foreach (['denni_sazba', 'vikend_cely'] as $pole) {
                                            $sazba = (float) \App\Models\ZapujcniZarizeni::whereKey($ids)->sum($pole);
                                            if ($sazba > 0 && (float) $get('../../' . $pole) < $sazba) {
                                                $set('../../' . $pole, $sazba);
                                            }
                                        }
                                    }),
                                TextInput::make('nazev')->label('Zařízení')->required()->columnSpan(2)
                                    ->datalist(fn () => \App\Models\ZapujckaPolozka::query()->distinct()->orderBy('nazev')->pluck('nazev')->all()),
                                TextInput::make('seriove_cislo')->label('Sériové číslo'),
                                TextInput::make('hodnota')->label('Hodnota')->numeric()->suffix('Kč')
                                    ->helperText('Pro případ ztráty / poškození.'),
                                TextInput::make('prislusenstvi')->label('Příslušenství')->columnSpan(2)
                                    ->placeholder('napájení, HDMI, ovladač…'),
                                TextInput::make('stav_pri_zapujceni')->label('Stav při zapůjčení')->columnSpan(3)
                                    ->placeholder('funkční, bez poškození'),
                                TextInput::make('stav_pri_vraceni')->label('Stav při vrácení')->columnSpan(3)
                                    ->visible(fn ($record) => $record?->zapujcka?->stav === 'vraceno'),
                            ]),
                    ]),
            ]);
    }
}
