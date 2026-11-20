<?php

namespace App\Filament\Resources\ZapujcniTyps;

use App\Filament\Resources\ZapujcniTyps\Pages\CreateZapujcniTyp;
use App\Filament\Resources\ZapujcniTyps\Pages\EditZapujcniTyp;
use App\Filament\Resources\ZapujcniTyps\Pages\ListZapujcniTyps;
use App\Models\ZapujcniTyp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Typy zápůjčních zařízení = šablony ceníku, které se předvyplní fyzickým zařízením. */
class ZapujcniTypResource extends Resource
{
    protected static ?string $model = ZapujcniTyp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Zápůjčky';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Typy a ceník';

    protected static ?string $modelLabel = 'typ zařízení';

    protected static ?string $pluralModelLabel = 'Typy zařízení a ceník';

    protected static ?string $recordTitleAttribute = 'nazev';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextInput::make('nazev')->label('Typ zařízení')->required()->columnSpan(2)
                    ->placeholder('např. PlayStation 5 Digital'),
                TextInput::make('poradi')->label('Pořadí')->numeric()->default(0),
                TextInput::make('denni_sazba')->label('Cena za den (Po–Ne)')->numeric()->suffix('Kč / den'),
                TextInput::make('vikend_cely')->label('Celý víkend (Pá–Ne)')->numeric()->suffix('Kč'),
                TextInput::make('kauce')->label('Kauce')->numeric()->suffix('Kč'),
                TextInput::make('hodnota')->label('Hodnota zařízení')->numeric()->suffix('Kč')
                    ->helperText('Výchozí hodnota pro případ ztráty / poškození.'),
                TextInput::make('prislusenstvi')->label('Výchozí příslušenství')->columnSpan(2)
                    ->placeholder('napájení, HDMI, ovladač'),
                TextInput::make('klicova_slova')->label('Klíčová slova pro automatické přiřazení')->columnSpanFull()
                    ->placeholder('ps5, playstation 5')
                    ->helperText('Oddělená čárkou. Zařízení převedené z bazaru / skladu, jehož název obsahuje některé slovo (nebo název typu), dostane tento typ automaticky.'),
                Textarea::make('protokol_poznamka')->label('Poznámka do předávacího protokolu')->rows(3)->columnSpanFull()
                    ->helperText('Vytiskne se v protokolu u každé zápůjčky s tímto typem zařízení (např. stav při předání, co musí zákazník udělat před vrácením).'),
                Textarea::make('poznamka')->label('Interní poznámka')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nazev')->label('Typ zařízení')->weight('medium')->searchable()
                    ->description(fn (ZapujcniTyp $r) => $r->prislusenstvi),
                TextColumn::make('denni_sazba')->label('Den')->money('CZK', decimalPlaces: 0)->placeholder('—'),
                TextColumn::make('vikend_cely')->label('Víkend Pá–Ne')->money('CZK', decimalPlaces: 0)->placeholder('—'),
                TextColumn::make('kauce')->label('Kauce')->money('CZK', decimalPlaces: 0)->placeholder('—'),
                TextColumn::make('zarizeni_count')->label('Kusů na skladě')->counts(['zarizeni' => fn ($q) => $q->where('aktivni', true)])->badge(),
            ])
            ->defaultSort('poradi')
            ->recordActions([
                EditAction::make(),
                Action::make('pouzit')->label('Použít ceník na kusy')->icon('heroicon-o-arrow-path')->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Ceny (den, víkend) a kauce tohoto typu se přepíšou u všech zařízení tohoto typu na zápůjčním skladu.')
                    ->action(fn (ZapujcniTyp $record) => Notification::make()->success()
                        ->title('Ceník použit na ' . $record->pouzitNaZarizeni() . ' zařízení')->send()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZapujcniTyps::route('/'),
            'create' => CreateZapujcniTyp::route('/create'),
            'edit' => EditZapujcniTyp::route('/{record}/edit'),
        ];
    }
}
