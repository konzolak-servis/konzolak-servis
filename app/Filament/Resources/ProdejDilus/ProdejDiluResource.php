<?php

namespace App\Filament\Resources\ProdejDilus;

use App\Filament\Resources\ProdejDilus\Pages\CreateProdejDilu;
use App\Filament\Resources\ProdejDilus\Pages\ListProdejDilus;
use App\Filament\Resources\Zakazniks\Schemas\ZakaznikForm;
use App\Models\ProdejDilu;
use App\Models\SkladPolozka;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ProdejDiluResource extends Resource
{
    protected static ?string $model = ProdejDilu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Sklad';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Prodej dílů';

    protected static ?string $modelLabel = 'prodej dílů';

    protected static ?string $pluralModelLabel = 'Prodej dílů';

    protected static ?string $recordTitleAttribute = 'cislo';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                ZakaznikForm::vyber(Select::make('zakaznik_id')
                    ->label('Zákazník (nepovinné)')
                    ->relationship('zakaznik', 'jmeno')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->vyber_label)
                    ->searchable()->preload())
                    ->columnSpan(2),
                DatePicker::make('datum')->label('Datum prodeje')->default(now())->native(false)->required(),
                TextInput::make('kupujici')->label('Kupující (když není v zákaznících)')->columnSpan(2)
                    ->visible(fn (Get $get) => blank($get('zakaznik_id'))),
                Radio::make('platba')->label('Platba')->options(ProdejDilu::PLATBY)->default('hotove')->required()
                    ->helperText('Hotově a QR na místě se odešle do EET, převod na dálku ne.')->columnSpan(2),
                Radio::make('zaruka_mesice')->label('Odpovědnost za vady')
                    ->options([24 => '24 měsíců (nový díl)', 12 => '12 měsíců (použitý díl)'])->default(24),
            ]),

            Section::make('Díly')->schema([
                Repeater::make('polozky')
                    ->relationship()
                    ->label('')
                    ->addActionLabel('Přidat díl')
                    ->minItems(1)->defaultItems(1)
                    ->columns(6)
                    ->schema([
                        Select::make('sklad_polozka_id')->label('Díl ze skladu')->columnSpan(3)->required()
                            ->options(fn ($state) => SkladPolozka::query()
                                ->where(fn ($q) => $q->where('mnozstvi_skladem', '>', 0)->when($state, fn ($w) => $w->orWhere('id', $state)))
                                ->orderBy('nazev')->get()
                                ->mapWithKeys(fn ($s) => [$s->id => $s->nazev . '  (skladem ' . rtrim(rtrim(number_format((float) $s->mnozstvi_skladem, 3, ',', ''), '0'), ',') . ' ks)'])->all())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn ($state, $set) => $set('nazev', SkladPolozka::find($state)?->nazev)),
                        TextInput::make('mnozstvi')->label('Ks')->numeric()->default(1)->minValue(0.001)->required()
                            ->maxValue(fn (Get $get) => (float) (SkladPolozka::find($get('sklad_polozka_id'))?->mnozstvi_skladem ?? 9999)),
                        TextInput::make('cena_ks')->label('Prodejní cena / ks')->numeric()->suffix('Kč')->required()->columnSpan(2)
                            ->helperText(fn (Get $get) => ($s = SkladPolozka::find($get('sklad_polozka_id')))
                                ? 'Nákupní cena: ' . number_format((float) $s->cena_ks_prumer, 0, ',', ' ') . ' Kč/ks' : null),
                        TextInput::make('nazev')->label('Název na dokladu')->columnSpanFull()->required()
                            ->helperText('Doplní se ze skladu, můžeš upravit.'),
                    ]),
            ]),

            Textarea::make('poznamka')->label('Poznámka (na doklad)')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cislo')->label('Číslo')->searchable()->sortable()->weight('medium')
                    ->description(fn (ProdejDilu $r) => $r->datum?->format('d.m.Y')),
                TextColumn::make('kupujici_nazev')->label('Kupující')
                    ->state(fn (ProdejDilu $r) => $r->kupujiciNazev()),
                TextColumn::make('dily')->label('Díly')->wrap()
                    ->state(fn (ProdejDilu $r) => $r->polozky->map(fn ($p) => ((float) $p->mnozstvi != 1 ? rtrim(rtrim(number_format((float) $p->mnozstvi, 3, ',', ''), '0'), ',') . '× ' : '') . $p->nazev)->implode(', ')),
                TextColumn::make('celkem')->label('Celkem')->money('CZK', decimalPlaces: 0)->sortable()
                    ->description(fn (ProdejDilu $r) => 'marže ' . number_format($r->marze(), 0, ',', ' ') . ' Kč'),
                TextColumn::make('platba')->label('Platba')->badge()
                    ->formatStateUsing(fn ($state) => ProdejDilu::PLATBY[$state] ?? $state),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with(['polozky', 'zakaznik']))
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('doklad')->label('Doklad')->icon('heroicon-o-printer')->color('primary')->button()
                    ->url(fn (ProdejDilu $r) => $r->tiskUrl())->openUrlInNewTab(),
                DeleteAction::make()->label('Storno')
                    ->modalHeading(fn (ProdejDilu $r) => 'Stornovat prodej ' . $r->cislo)
                    ->modalDescription('Díly se vrátí na sklad a příjem se odebere z peněžního deníku (v EET se zapíše storno).'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProdejDilus::route('/'),
            'create' => CreateProdejDilu::route('/create'),
        ];
    }
}
