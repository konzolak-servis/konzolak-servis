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
                    ->live()
                    ->required(),
                Toggle::make('uctovat')
                    ->label('Účtovat zákazníkovi')
                    ->default(true)
                    ->helperText('Vypni u dílu, jehož cena je už zahrnutá v ceně opravy, nebo který si nese zákazník sám.'),
                TextInput::make('nazev')->label('Název')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('mnozstvi')->label('Množství')->numeric()->default(1)->required(),
                TextInput::make('cena_ks')->label('Cena / ks (účtovaná zákazníkovi)')->numeric()->default(0)->required()->suffix('Kč'),
                TextInput::make('naklad_interni')->label('Skutečný náklad / ks (skryté, jen pro mě)')
                    ->numeric()->suffix('Kč')
                    ->helperText('Nezávislé na ceně výše – změna účtované ceny zisk nezkreslí.')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('typ') === 'material')
                    ->columnSpanFull(),
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
                TextColumn::make('naklad_interni')->label('Náklad/ks')->money('CZK')
                    ->toggleable()->toggledHiddenByDefault(),
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
                            ->label(fn () => $this->slevaCastka() > 0 ? 'Účtováno celkem (před slevou)' : 'Účtováno celkem')
                            ->query(fn ($query) => $query->where('uctovat', true))
                            ->money('CZK'),
                        Summarizer::make('sleva')
                            ->label(function () {
                                $z = $this->zakazkaCerstva();

                                return $z->slevaPopis() . ($z->sleva_kod ? ' · ' . $z->sleva_kod : '');
                            })
                            ->using(fn (Builder $query) => -1 * $this->slevaCastka())
                            ->visible(fn () => $this->slevaCastka() > 0)
                            ->money('CZK'),
                        Summarizer::make('po_sleve')
                            ->label('Cena po slevě (skutečná)')
                            ->using(fn (Builder $query) => (float) (clone $query)->where('uctovat', true)->sum('cena_celkem') - $this->slevaCastka())
                            ->visible(fn () => $this->slevaCastka() > 0)
                            ->money('CZK'),
                        Summarizer::make('doprava')
                            ->label('Doprava naúčtovaná zákazníkovi')
                            ->using(fn (Builder $query) => (float) $this->zakazkaCerstva()->cena_dopravy)
                            ->visible(fn () => (float) $this->zakazkaCerstva()->cena_dopravy > 0)
                            ->money('CZK'),
                        Summarizer::make('naklad_materialu')
                            ->label('Náklad na materiál')
                            ->using(fn (Builder $query) => (float) (clone $query)
                                ->where('typ', 'material')
                                ->sum(\Illuminate\Support\Facades\DB::raw('mnozstvi * COALESCE(naklad_interni, 0)')))
                            ->money('CZK'),
                        Summarizer::make('postovne')
                            ->label('Poštovné (platím já)')
                            ->using(fn (Builder $query) => $this->postovneNaklad())
                            ->visible(fn () => $this->postovneNaklad() > 0)
                            ->money('CZK'),
                        Summarizer::make('zisk')
                            ->label('Zisk (po odečtení nákladů)')
                            ->using(function (Builder $query) {
                                $uctovano = (float) (clone $query)->where('uctovat', true)->sum('cena_celkem');
                                $naklad = (float) (clone $query)
                                    ->where('typ', 'material')
                                    ->sum(\Illuminate\Support\Facades\DB::raw('mnozstvi * COALESCE(naklad_interni, 0)'));

                                // Sleva snižuje příjem, zákazníkovi naúčtovaná doprava ho zvyšuje,
                                // poštovné zaplacené servisem je náklad.
                                $z = $this->getOwnerRecord()->fresh();

                                return $uctovano - $z->slevaCastka() + (float) $z->cena_dopravy
                                    - $naklad - $this->postovneNaklad();
                            })
                            ->money('CZK'),
                    ]),
            ])
            ->headerActions([
                Action::make('z_ceniku')
                    ->label('Práce z ceníku')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        \Filament\Forms\Components\ToggleButtons::make('razeni')->label('Řazení')
                            ->options(['caste' => 'Nejčastější', 'abeceda' => 'Abecedně', 'zarizeni' => 'Podle zařízení', 'cena' => 'Podle ceny'])
                            ->default('caste')->inline()->live()->dehydrated(false),
                        Select::make('cenik_polozka_id')
                            ->label('Úkon z ceníku')
                            ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => $this->cenikPodlePouziti($get('razeni') ?: 'caste'))
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
                        \Filament\Forms\Components\ToggleButtons::make('razeni')->label('Řazení')
                            ->options(['caste' => 'Nejčastější', 'abeceda' => 'Abecedně', 'skladem' => 'Nejvíc skladem', 'cena' => 'Podle ceny'])
                            ->default('caste')->inline()->live()->dehydrated(false),
                        Select::make('sklad_polozka_id')
                            ->label('Skladová položka')
                            ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => $this->skladPodlePouziti($get('razeni') ?: 'caste'))
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
                            'naklad_interni' => $s->cena_ks_prumer,
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

    /** Poštovné zaplacené servisem (aktuální hodnota ze zakázky). */
    private function postovneNaklad(): float
    {
        return (float) \App\Models\Zakazka::whereKey($this->getOwnerRecord()->getKey())->value('postovne_naklad');
    }

    /** Ceník pro výběr práce. Řazení: caste (nejčastěji účtované) | abeceda | zarizeni (podle zařízení) | cena. */
    private function cenikPodlePouziti(string $razeni = 'caste'): array
    {
        $pocty = \Illuminate\Support\Facades\DB::table('zakazka_polozky')
            ->where('typ', 'prace')
            ->selectRaw('nazev, COUNT(*) as pocet')
            ->groupBy('nazev')
            ->pluck('pocet', 'nazev');

        $polozky = CenikPolozka::where('aktivni', true)->orderBy('kategorie')->orderBy('poradi')->get();
        $abc = fn ($t) => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $t));

        $polozky = match ($razeni) {
            'abeceda' => $polozky->sortBy(fn ($c) => $abc($c->nazev)),
            'cena' => $polozky->sortBy(fn ($c) => (float) $c->cena),
            'zarizeni' => $polozky,
            default => $polozky->sortByDesc(fn ($c) => (int) ($pocty[$c->nazev] ?? 0)),
        };

        return $polozky->mapWithKeys(fn ($c) => [
            $c->id => "{$c->nazev}  –  {$c->cena} Kč" . (($pocty[$c->nazev] ?? 0) > 0 ? "  ·  {$pocty[$c->nazev]}×" : ''),
        ])->all();
    }

    /** Sklad pro výběr materiálu. Řazení: caste | abeceda | skladem (nejvíc kusů) | cena. */
    private function skladPodlePouziti(string $razeni = 'caste'): array
    {
        $pocty = \Illuminate\Support\Facades\DB::table('zakazka_polozky')
            ->whereNotNull('sklad_polozka_id')
            ->selectRaw('sklad_polozka_id, COUNT(*) as pocet')
            ->groupBy('sklad_polozka_id')
            ->pluck('pocet', 'sklad_polozka_id');

        $polozky = SkladPolozka::where('aktivni', true)->where('spotrebni', false)->orderBy('nazev')->get();
        $abc = fn ($t) => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $t));

        $polozky = match ($razeni) {
            'abeceda' => $polozky->sortBy(fn ($s) => $abc($s->nazev)),
            'skladem' => $polozky->sortByDesc(fn ($s) => (float) $s->mnozstvi_skladem),
            'cena' => $polozky->sortBy(fn ($s) => (float) $s->cena_ks_prumer),
            default => $polozky->sortByDesc(fn ($s) => (int) ($pocty[$s->id] ?? 0)),
        };

        return $polozky->mapWithKeys(fn ($s) => [
            $s->id => "{$s->nazev}  (skladem {$s->mnozstvi_skladem}, {$s->cena_ks_prumer} Kč/ks)"
                . (($pocty[$s->id] ?? 0) > 0 ? "  ·  {$pocty[$s->id]}×" : ''),
        ])->all();
    }

    private function zakazkaCerstva(): \App\Models\Zakazka
    {
        return \App\Models\Zakazka::findOrFail($this->getOwnerRecord()->getKey());
    }

    /** Aktuální částka slevy zakázky (Kč). */
    private function slevaCastka(): float
    {
        return $this->zakazkaCerstva()->slevaCastka();
    }
}
