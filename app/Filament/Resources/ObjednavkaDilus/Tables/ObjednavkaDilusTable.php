<?php

namespace App\Filament\Resources\ObjednavkaDilus\Tables;

use App\Models\ObjednavkaDilu;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ObjednavkaDilusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stav')
                    ->label('Stav')
                    ->badge()
                    ->icon(fn (?string $state): string => match ($state) {
                        'dorazilo' => 'heroicon-m-check-circle',
                        'zruseno' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-clock',
                    })
                    ->formatStateUsing(fn (?string $state): string => ObjednavkaDilu::STAVY[$state] ?? ($state ?: 'Objednáno'))
                    ->color(fn (?string $state): string => match ($state) {
                        'dorazilo' => 'success',
                        'zruseno' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('nazev_dilu')->label('Díl')->searchable()->wrap()->weight('medium')
                    ->description(fn (ObjednavkaDilu $r) => $r->cislo . ($r->dodavatel ? ' · ' . $r->dodavatel : '')),
                TextColumn::make('zakazka.cislo')->label('Zakázka')
                    ->badge()->color('gray')->placeholder('—')
                    ->url(fn ($record) => $record->zakazka
                        ? \App\Filament\Resources\Zakazkas\ZakazkaResource::getUrl('edit', ['record' => $record->zakazka_id])
                        : null),
                TextColumn::make('datum_objednavky')->label('Objednáno')->date('d.m.Y')->sortable(),
                TextColumn::make('ocekavane_doruceni')->label('Očekáváno / doručeno')->date('d.m.Y')->sortable()
                    ->placeholder('—')
                    ->description(fn (ObjednavkaDilu $r) => $r->stav === 'dorazilo' && $r->doruceno_datum
                        ? '✓ doručeno ' . $r->doruceno_datum->format('d.m.Y')
                        : null)
                    ->color(fn (ObjednavkaDilu $r) => $r->stav === 'objednano' && $r->ocekavane_doruceni
                        && $r->ocekavane_doruceni->isPast() ? 'danger' : null),
                TextColumn::make('cena_odhad')->label('Odhad')->money('CZK')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('stav')->label('Stav')->options(ObjednavkaDilu::STAVY)
                    ->default('objednano'),
            ])
            ->defaultSort('datum_objednavky', 'desc')
            ->recordActions([
                Action::make('dorazilo')
                    ->label('Díl dorazil')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn (ObjednavkaDilu $record) => $record->stav !== 'dorazilo')
                    ->requiresConfirmation()
                    ->modalHeading('Potvrdit doručení dílu')
                    ->modalDescription(fn (ObjednavkaDilu $record) => $record->nazev_dilu
                        . ($record->zakazka ? ' · zakázka ' . $record->zakazka->cislo . ' se vrátí do rozpracovaných' : ''))
                    ->modalSubmitActionLabel('Ano, díl dorazil')
                    ->successNotificationTitle('Označeno jako doručené')
                    ->action(fn (ObjednavkaDilu $record) => $record->update([
                        'stav' => 'dorazilo',
                        'doruceno_datum' => now()->toDateString(),
                    ])),
                Action::make('naskladnit')
                    ->label('Naskladnit a zaúčtovat')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->button()
                    ->visible(fn (ObjednavkaDilu $record) => $record->stav === 'dorazilo' && (! $record->nakup_id || ! \App\Models\Nakup::whereKey($record->nakup_id)->value('naskladneno')))
                    ->modalHeading(fn (ObjednavkaDilu $record) => 'Naskladnit: ' . $record->nazev_dilu)
                    ->modalDescription('Vytvoří nákup, přidá díl na sklad (aby šel vybrat v zakázce jako „Materiál ze skladu") a zapíše výdaj do peněžního deníku. Nelze vzít zpět.')
                    ->modalSubmitActionLabel('Naskladnit')
                    ->schema(fn (ObjednavkaDilu $record) => [
                        TextInput::make('castka')->label('Cena dílu celkem')->numeric()->suffix('Kč')->required()
                            ->default((float) ($record->nakup_id
                                ? (\App\Models\NakupPolozka::where('nakup_id', $record->nakup_id)->sum('castka_celkem') ?: $record->cena_odhad)
                                : $record->cena_odhad))
                            ->helperText('Skutečně zaplacená cena za ' . rtrim(rtrim(number_format((float) $record->mnozstvi, 3, ',', ' '), '0'), ',') . ' ks (bez poštovného).'),
                        TextInput::make('postovne')->label('Poštovné / doprava')->numeric()->suffix('Kč')
                            ->default((float) ($record->nakup_id ? \App\Models\Nakup::whereKey($record->nakup_id)->value('postovne') : 0))
                            ->helperText('Přičte se k výdaji v deníku, do skladové ceny dílu se nepočítá.'),
                        DatePicker::make('datum')->label('Datum nákupu')->native(false)
                            ->default($record->nakup_id
                                ? \App\Models\Nakup::whereKey($record->nakup_id)->value('datum')
                                : ($record->doruceno_datum ?? now()))
                            ->helperText('Naskladnění se zapíše dnešním dnem (kdy díl dorazil).'),
                        Toggle::make('neskladovat')->label('Nepočítat jako materiál')
                            ->default(false)->live()
                            ->helperText('Díl se nenaskladní a výdaj půjde do peněžního deníku jako „Náklady firmy" (ne jako materiál). Hodí se u nářadí, vybavení apod.'),
                        Toggle::make('do_zakazky')->label('Rovnou použít v zakázce ' . ($record->zakazka?->cislo ?? ''))
                            ->default((bool) $record->zakazka)
                            ->visible(fn (Get $get) => (bool) $record->zakazka && ! $get('neskladovat'))
                            ->helperText('Díl se vydá ze skladu do zakázky (jako „Materiál ze skladu", cena dílu se nepřičítá zákazníkovi).'),
                    ])
                    ->action(function (ObjednavkaDilu $record, array $data) {
                        self::naskladnit($record, $data);
                    }),
                Action::make('zpet_objednano')
                    ->label('Zpět na objednáno')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (ObjednavkaDilu $record) => $record->stav === 'dorazilo')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Vráceno na „Objednáno"')
                    ->action(fn (ObjednavkaDilu $record) => $record->update([
                        'stav' => 'objednano',
                        'doruceno_datum' => null,
                    ])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Díl z objednávky → nákup → sklad (+ volitelně rovnou do zakázky). */
    private static function naskladnit(ObjednavkaDilu $o, array $data): void
    {
        $existujici = $o->nakup_id ? \App\Models\Nakup::with('polozky')->find($o->nakup_id) : null;
        if ($existujici && $existujici->naskladneno) {
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($o, $data, $existujici) {
            if ($existujici) {
                // k objednávce už byl nákup zadán (zaplaceno, díl ještě nedorazil) – jen se naskladní
                $nakup = $existujici;
                $nakup->update([
                    'datum' => $data['datum'] ?? $nakup->datum,
                    'postovne' => (float) ($data['postovne'] ?? $nakup->postovne),
                ]);
                $polozka = $nakup->polozky->first();
                $polozka->update([
                    'castka_celkem' => (float) $data['castka'],
                    'neskladovat' => ! empty($data['neskladovat']),
                ]);
            } else {
                $nakup = \App\Models\Nakup::create([
                    'dodavatel' => $o->dodavatel,
                    'datum' => $data['datum'] ?? now()->toDateString(),
                    'postovne' => (float) ($data['postovne'] ?? 0),
                ]);
                $polozka = $nakup->polozky()->create([
                    'nazev' => $o->nazev_dilu,
                    'mnozstvi_ks' => (float) $o->mnozstvi,
                    'castka_celkem' => (float) $data['castka'],
                    'zakazka_id' => $o->zakazka_id,
                    'neskladovat' => ! empty($data['neskladovat']),
                ]);
            }
            $nakup->load('polozky');
            $nakup->naskladnit();

            $o->update(['nakup_id' => $nakup->id]);

            if (empty($data['neskladovat']) && ! empty($data['do_zakazky']) && $o->zakazka) {
                $sklad = $polozka->fresh()->skladPolozka;
                $mnozstvi = (float) $o->mnozstvi;
                $sklad->vydej($mnozstvi, ['zdroj' => 'zakazka', 'zakazka_id' => $o->zakazka_id]);
                $o->zakazka->polozky()->create([
                    'typ' => 'material',
                    'uctovat' => false,
                    'sklad_polozka_id' => $sklad->id,
                    'nazev' => $sklad->nazev,
                    'mnozstvi' => $mnozstvi,
                    'cena_ks' => $sklad->cena_ks_prumer,
                    'naklad_interni' => $sklad->cena_ks_prumer,
                ]);
            }
        });

        Notification::make()
            ->title(! empty($data['neskladovat']) ? 'Náklad zaúčtován' : 'Díl naskladněn')
            ->body(! empty($data['neskladovat'])
                ? 'Zaúčtováno jako náklad firmy, na sklad se nepřidalo.'
                : (! empty($data['do_zakazky']) && $o->zakazka
                    ? 'Je na skladu i přidaný do zakázky ' . $o->zakazka->cislo . '.'
                    : 'Najdeš ho ve Skladu a v zakázce přes „Materiál ze skladu".'))
            ->success()
            ->send();
    }
}
