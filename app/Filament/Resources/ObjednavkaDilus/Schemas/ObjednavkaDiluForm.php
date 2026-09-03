<?php

namespace App\Filament\Resources\ObjednavkaDilus\Schemas;

use App\Models\ObjednavkaDilu;
use App\Models\Zakazka;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ObjednavkaDiluForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('cislo')->label('Číslo')->disabled()->dehydrated(false)
                        ->placeholder('přiřadí se automaticky'),
                    Select::make('stav')->label('Stav')->options(ObjednavkaDilu::STAVY)->default('objednano')->required(),

                    TextInput::make('nazev_dilu')->label('Název dílu')->required()->columnSpanFull(),
                    TextInput::make('dodavatel')->label('Dodavatel')
                        ->datalist(['Alza', 'Allegro', 'Konzoliste', 'Hadex', 'AliExpress', 'GM Electronic']),
                    TextInput::make('mnozstvi')->label('Množství')->numeric()->default(1),
                    TextInput::make('cena_odhad')->label('Odhad ceny')->numeric()->suffix('Kč'),

                    Select::make('zakazka_id')->label('K zakázce')
                        ->options(fn () => Zakazka::whereNotIn('stav', ['vydano', 'storno'])
                            ->with('zakaznik')->get()
                            ->mapWithKeys(fn ($z) => [$z->id => $z->cislo . ' – ' . ($z->zakaznik?->nazev ?? '')]))
                        ->searchable(),

                    DatePicker::make('datum_objednavky')->label('Objednáno dne')->default(now())->native(false),
                    DatePicker::make('ocekavane_doruceni')->label('Očekávané doručení')->native(false),
                    DatePicker::make('doruceno_datum')->label('Doručeno dne')->native(false),

                    Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }
}
