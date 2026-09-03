<?php

namespace App\Filament\Resources\ObjednavkaDilus\Tables;

use App\Models\ObjednavkaDilu;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
}
