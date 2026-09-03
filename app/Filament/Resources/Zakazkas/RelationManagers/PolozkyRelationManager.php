<?php

namespace App\Filament\Resources\Zakazkas\RelationManagers;

use App\Models\CenikPolozka;
use App\Models\SkladPolozka;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;

class PolozkyRelationManager extends RelationManager
{
    protected static string $relationship = 'polozky';

    protected static ?string $title = 'Položky (práce a materiál)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('typ')
                    ->label('Typ')
                    ->options(['prace' => 'Práce', 'material' => 'Materiál / díl'])
                    ->default('prace')
                    ->required(),
                Toggle::make('uctovat')
                    ->label('Účtovat zákazníkovi')
                    ->default(true)
                    ->helperText('Vypni u dílu, jehož cena je už zahrnutá v ceně opravy, nebo který si nese zákazník sám.'),
                TextInput::make('nazev')->label('Název')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('mnozstvi')->label('Množství')->numeric()->default(1)->required(),
                TextInput::make('cena_ks')->label('Cena / ks')->numeric()->default(0)->required()->suffix('Kč'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nazev')
            ->columns([
                TextColumn::make('typ')->label('Typ')->badge()
                    ->formatStateUsing(fn ($state) => $state === 'material' ? 'Materiál' : 'Práce')
                    ->color(fn ($state) => $state === 'material' ? 'warning' : 'info'),
                TextColumn::make('nazev')->label('Název')->wrap(),
                TextColumn::make('mnozstvi')->label('Množ.')->numeric(),
                TextColumn::make('cena_ks')->label('Cena/ks')->money('CZK'),
                ToggleColumn::make('uctovat')
                    ->label('Účtovat')
                    ->tooltip('Zapnuto = přičítá se k ceně a je na protokolu. Vypnuto = jen evidence (sklad).')
                    ->afterStateUpdated(fn ($record) => $record->zakazka?->prepocti()),
                TextColumn::make('cena_celkem')->label('Celkem')
                    ->money('CZK')
                    ->color(fn ($record) => $record->uctovat ? null : 'gray')
                    ->formatStateUsing(fn ($state, $record) => $record->uctovat
                        ? number_format($state, 0, ',', ' ') . ' Kč'
                        : '(' . number_format($state, 0, ',', ' ') . ' Kč)')
                    ->summarize([
                        Sum::make('uctovano')
                            ->label('Účtováno celkem')
                            ->query(fn ($query) => $query->where('uctovat', true))
                            ->money('CZK'),
                        Summarizer::make('naklad_materialu')
                            ->label('Náklad na materiál')
                            ->using(fn (Builder $query) => (float) (clone $query)
                                ->where('typ', 'material')
                                ->whereNotNull('sklad_polozka_id')
                                ->sum(\Illuminate\Support\Facades\DB::raw('mnozstvi * cena_ks')))
                            ->money('CZK'),
                        Summarizer::make('zisk')
                            ->label('Zisk (po odečtení materiálu)')
                            ->using(function (Builder $query) {
                                $uctovano = (float) (clone $query)->where('uctovat', true)->sum('cena_celkem');
                                $naklad = (float) (clone $query)
                                    ->where('typ', 'material')
                                    ->whereNotNull('sklad_polozka_id')
                                    ->sum(\Illuminate\Support\Facades\DB::raw('mnozstvi * cena_ks'));

                                return $uctovano - $naklad;
                            })
                            ->money('CZK'),
                    ]),
            ])
            ->headerActions([
                Action::make('z_ceniku')
                    ->label('Práce z ceníku')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Select::make('cenik_polozka_id')
                            ->label('Úkon z ceníku')
                            ->options(CenikPolozka::where('aktivni', true)->orderBy('kategorie')->orderBy('poradi')
                                ->get()->mapWithKeys(fn ($c) => [$c->id => "{$c->nazev}  –  {$c->cena} Kč"]))
                            ->searchable()
                            ->required(),
                        TextInput::make('mnozstvi')->label('Množství')->numeric()->default(1)->required(),
                    ])
                    ->action(function (array $data) {
                        $c = CenikPolozka::find($data['cenik_polozka_id']);
                        $this->getOwnerRecord()->polozky()->create([
                            'typ' => 'prace',
                            'uctovat' => true,
                            'nazev' => $c->nazev,
                            'mnozstvi' => $data['mnozstvi'],
                            'cena_ks' => $c->cena,
                        ]);
                    }),

                Action::make('material_ze_skladu')
                    ->label('Materiál ze skladu')
                    ->icon('heroicon-o-cube')
                    ->color('warning')
                    ->schema([
                        Select::make('sklad_polozka_id')
                            ->label('Skladová položka')
                            ->options(SkladPolozka::where('aktivni', true)->orderBy('nazev')
                                ->get()->mapWithKeys(fn ($s) => [
                                    $s->id => "{$s->nazev}  (skladem {$s->mnozstvi_skladem}, {$s->cena_ks_prumer} Kč/ks)",
                                ]))
                            ->searchable()
                            ->required(),
                        TextInput::make('mnozstvi')->label('Počet ks')->numeric()->default(1)->required(),
                        Toggle::make('uctovat')
                            ->label('Přičíst k ceně a vypsat zákazníkovi')
                            ->default(false)
                            ->helperText('Standardně je cena dílu už v ceně opravy – nechej vypnuté. Zapni jen u speciálního dílu účtovaného zvlášť.'),
                    ])
                    ->action(function (array $data) {
                        $s = SkladPolozka::find($data['sklad_polozka_id']);
                        $mnozstvi = (float) $data['mnozstvi'];

                        $s->vydej($mnozstvi, [
                            'zdroj' => 'zakazka',
                            'zakazka_id' => $this->getOwnerRecord()->getKey(),
                        ]);

                        $this->getOwnerRecord()->polozky()->create([
                            'typ' => 'material',
                            'uctovat' => (bool) ($data['uctovat'] ?? false),
                            'sklad_polozka_id' => $s->id,
                            'nazev' => $s->nazev,
                            'mnozstvi' => $mnozstvi,
                            'cena_ks' => $s->cena_ks_prumer,
                        ]);
                    }),

                Action::make('dil_zakaznika')
                    ->label('Díl od zákazníka')
                    ->icon('heroicon-o-gift')
                    ->color('gray')
                    ->schema([
                        TextInput::make('nazev')->label('Název dílu')->required(),
                        TextInput::make('cena_ks')->label('Účtovaná cena (0 = zdarma)')
                            ->numeric()->default(0)->minValue(0)->suffix('Kč'),
                    ])
                    ->action(function (array $data) {
                        $cena = (float) ($data['cena_ks'] ?? 0);
                        $this->getOwnerRecord()->polozky()->create([
                            'typ' => 'material',
                            'uctovat' => $cena > 0,
                            'nazev' => $data['nazev'] . ' (díl zákazníka)',
                            'mnozstvi' => 1,
                            'cena_ks' => $cena,
                        ]);
                    }),

                CreateAction::make()
                    ->label('Vlastní položka')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->modalHeading('Vlastní položka')
                    ->modalDescription('Napiš libovolnou práci nebo materiál ručně – nemusí být v ceníku ani ve skladu.')
                    ->modalSubmitActionLabel('Přidat do seznamu'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
