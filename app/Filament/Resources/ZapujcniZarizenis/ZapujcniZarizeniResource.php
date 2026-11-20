<?php

namespace App\Filament\Resources\ZapujcniZarizenis;

use App\Filament\Resources\Zapujckas\ZapujckaResource;
use App\Filament\Resources\ZapujcniZarizenis\Pages\CreateZapujcniZarizeni;
use App\Filament\Resources\ZapujcniZarizenis\Pages\EditZapujcniZarizeni;
use App\Filament\Resources\ZapujcniZarizenis\Pages\ListZapujcniZarizenis;
use App\Models\ZapujcniZarizeni;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ZapujcniZarizeniResource extends Resource
{
    protected static ?string $model = ZapujcniZarizeni::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Zápůjčky';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Zápůjční sklad';

    protected static ?string $modelLabel = 'zařízení k zápůjčce';

    protected static ?string $pluralModelLabel = 'Zařízení k zápůjčce';

    protected static ?string $recordTitleAttribute = 'nazev';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                \Filament\Forms\Components\Select::make('obchod_id')->label('Kus z bazaru')
                    ->columnSpanFull()
                    ->options(fn ($state) => \App\Models\Obchod::query()
                        ->where(fn ($q) => $q->where('prodano', false)->when($state, fn ($w) => $w->orWhere('id', $state)))
                        ->orderByDesc('datum')->get()
                        ->mapWithKeys(fn ($o) => [$o->id => $o->cislo . ' · ' . $o->nazev . ($o->seriove_cislo ? ' · SN ' . $o->seriove_cislo : '')])->all())
                    ->searchable()
                    ->live()
                    ->placeholder('není z bazaru')
                    ->helperText('Podle sériového čísla se spáruje samo. Kus z bazaru se tam pak vede jako „Na zápůjčním skladu".')
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if (! $state || ! ($o = \App\Models\Obchod::find($state))) {
                            return;
                        }
                        foreach (['nazev' => $o->nazev, 'seriove_cislo' => $o->seriove_cislo,
                            'stav_popis' => $o->stav_popis ? \Illuminate\Support\Str::limit($o->stav_popis, 250, '') : null,
                            'hodnota' => $o->naklady_celkem ?: $o->cena] as $pole => $hodnota) {
                            if (blank($get($pole))) {
                                $set($pole, $hodnota);
                            }
                        }
                    }),
                TextInput::make('nazev')->label('Zařízení')->required()->columnSpan(2)
                    ->placeholder('např. PS5 Slim digital, DualSense bílý'),
                TextInput::make('seriove_cislo')->label('Sériové číslo'),
                TextInput::make('prislusenstvi')->label('Příslušenství (zapůjčuje se s ním)')->columnSpan(2)
                    ->placeholder('napájecí kabel, HDMI, 1× ovladač'),
                TextInput::make('stav_popis')->label('Aktuální stav')->placeholder('funkční, bez poškození')
                    ->helperText('Po vrácení zápůjčky se přepíše stavem při vrácení.'),
                TextInput::make('hodnota')->label('Hodnota')->numeric()->suffix('Kč')
                    ->helperText('Pro případ ztráty / poškození – uvádí se v protokolu.'),
                TextInput::make('kauce')->label('Doporučená kauce')->numeric()->suffix('Kč')
                    ->helperText('Předvyplní se do zápůjčky.'),
                TextInput::make('denni_sazba')->label('Cena za den (Po–Ne)')->numeric()->suffix('Kč / den'),
                TextInput::make('vikend_cely')->label('Celý víkend (Pá–Ne)')->numeric()->suffix('Kč')
                    ->helperText('Použije se, když zápůjčka obsahuje pátek až neděli a vyjde levněji než 3 dny.'),
                Toggle::make('aktivni')->label('K zapůjčení')->default(true)->inline(false)
                    ->helperText('Vypni u vyřazeného / prodaného zařízení.'),
                Textarea::make('poznamka')->label('Poznámka')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nazev')->label('Zařízení')->searchable(['nazev', 'seriove_cislo'])->sortable()->weight('medium')
                    ->description(fn (ZapujcniZarizeni $r) => trim('Z-' . str_pad($r->id, 3, '0', STR_PAD_LEFT)
                        . ($r->seriove_cislo ? ' · SN ' . $r->seriove_cislo : '')
                        . ($r->puvodText() ? ' · z: ' . $r->puvodText() : ''), ' ·') ?: null),
                TextColumn::make('dostupnost')->label('Dostupnost')->badge()
                    ->getStateUsing(fn (ZapujcniZarizeni $r) => ! $r->aktivni ? 'Vyřazeno'
                        : (($p = $r->aktualniPolozka()) ? 'Zapůjčeno' : 'Volné'))
                    ->color(fn ($state) => match ($state) {
                        'Volné' => 'success',
                        'Zapůjčeno' => 'warning',
                        default => 'gray',
                    })
                    ->description(function (ZapujcniZarizeni $r) {
                        $p = $r->aktualniPolozka();
                        if (! $p) {
                            return null;
                        }
                        $z = $p->zapujcka;

                        return $z->cislo . ' · ' . ($z->zakaznik?->nazev ?? '') . ($z->vratit_do ? ' · do ' . $z->vratit_do->format('d.m.') : '');
                    })
                    ->url(fn (ZapujcniZarizeni $r) => ($p = $r->aktualniPolozka())
                        ? ZapujckaResource::getUrl('edit', ['record' => $p->zapujcka_id]) : null),
                TextColumn::make('prislusenstvi')->label('Příslušenství')->wrap()->toggleable(),
                TextColumn::make('stav_popis')->label('Stav')->wrap()->placeholder('—'),
                TextColumn::make('hodnota')->label('Hodnota')->money('CZK')->sortable()->toggleable(),
                TextColumn::make('kauce')->label('Kauce')->money('CZK')->placeholder('—'),
                TextColumn::make('denni_sazba')->label('Ceník')->placeholder('—')->toggleable()
                    ->formatStateUsing(fn ($state, ZapujcniZarizeni $record) => (new \App\Models\Zapujcka([
                        'denni_sazba' => $record->denni_sazba, 'vikend_sazba' => $record->vikend_sazba, 'vikend_cely' => $record->vikend_cely,
                    ]))->tarifText())->wrap(),
                TextColumn::make('polozky_count')->label('Zapůjčeno ×')->counts('polozky')->sortable()->toggleable(),
            ])
            ->filters([
                Filter::make('volne')->label('Jen volná')->toggle()
                    ->query(fn ($query) => $query->volne()),
                TernaryFilter::make('aktivni')->label('K zapůjčení')->default(true)
                    ->trueLabel('Ano')->falseLabel('Vyřazená')->placeholder('Vše'),
            ])
            ->defaultSort('nazev')
            ->recordActions([
                Action::make('zapujcit')->label('Zapůjčit')->icon('heroicon-o-arrow-right-circle')->color('primary')->button()
                    ->visible(fn (ZapujcniZarizeni $r) => $r->aktivni && ! $r->aktualniPolozka())
                    ->url(fn (ZapujcniZarizeni $r) => ZapujckaResource::getUrl('create', ['zarizeni' => $r->id])),
                EditAction::make(),
                Action::make('prevest_zpet')
                    ->label(fn (ZapujcniZarizeni $r) => $r->sklad_polozka_id ? 'Vrátit na sklad' : 'Vrátit do bazaru')
                    ->icon('heroicon-o-arrow-uturn-left')->color('gray')
                    ->visible(fn (ZapujcniZarizeni $r) => $r->aktivni && ($r->sklad_polozka_id || $r->obchod_id) && ! $r->aktualniPolozka())
                    ->requiresConfirmation()
                    ->modalDescription(fn (ZapujcniZarizeni $r) => $r->sklad_polozka_id
                        ? 'Kus se vrátí na sklad (+1 ks u „' . $r->skladPolozka?->nazev . '") a zmizí ze zápůjčního skladu.'
                        : 'Kus bude v bazaru znovu „Skladem" k prodeji a přestane se nabízet k zápůjčce.')
                    ->action(function (ZapujcniZarizeni $r) {
                        try {
                            $r->prevestZpet();
                            \Filament\Notifications\Notification::make()->success()->title('Převedeno zpět')->send();
                        } catch (\RuntimeException $e) {
                            \Filament\Notifications\Notification::make()->warning()->title($e->getMessage())->send();
                        }
                    }),
                Action::make('stitek')->label('Štítek')->icon('heroicon-o-tag')->color('primary')->button()
                    ->tooltip('Identifikační štítek 50×30 mm (PDF k tisku)')
                    ->url(fn (ZapujcniZarizeni $r) => route('tisk.zapujcni.stitek', ['zarizeni' => $r]))->openUrlInNewTab(),
                Action::make('stitek_png')->label('PNG')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->tooltip('Stáhnout štítek jako obrázek PNG')
                    ->url(fn (ZapujcniZarizeni $r) => route('tisk.zapujcni.stitek-png', ['zarizeni' => $r,
                        'soubor' => 'Stitek-zapujcka-' . $r->id . '.png'])),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkAction::make('stitky')->label('Tisk štítků vybraných')->icon('heroicon-o-tag')
                    ->action(fn ($records, $livewire) => $livewire->js('window.open(' . json_encode(
                        route('tisk.zapujcni.stitek', ['ids' => $records->pluck('id')->implode(',')])) . ', "_blank")'))
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZapujcniZarizenis::route('/'),
            'create' => CreateZapujcniZarizeni::route('/create'),
            'edit' => EditZapujcniZarizeni::route('/{record}/edit'),
        ];
    }
}
