<?php

namespace App\Filament\Resources\PenezniDeniks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PenezniDenikForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                DatePicker::make('datum')->label('Datum')->default(now())->native(false)->required(),
                Select::make('typ')->label('Typ')
                    ->options(['prijem' => 'Příjem', 'vydej' => 'Výdej'])->required(),
                TextInput::make('popis')->label('Popis')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('castka')->label('Částka')->numeric()->required()->suffix('Kč'),
                TextInput::make('kategorie')->label('Kategorie')
                    ->datalist(['Servis', 'Materiál', 'Náklady firmy', 'Vybavení', 'Ostatní'])
                    ->helperText('„Náklady firmy" = předplatné, software, doména, kancelář, spotřební '
                        . 'materiál, vybavení dílny… vše, co nejde do skladu dílů na opravy.'),
                TextInput::make('kde')->label('Kde (u výdeje)')->maxLength(255),
                Placeholder::make('zdroj_info')
                    ->label('Co to je a doklady')
                    ->columnSpanFull()
                    ->visible(fn (?\App\Models\PenezniDenik $record) => filled($record?->zdroj))
                    ->content(fn (?\App\Models\PenezniDenik $record) => new \Illuminate\Support\HtmlString($record?->zdrojHtml() ?? '')),
            ]);
    }
}
