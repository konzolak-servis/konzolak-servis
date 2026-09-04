<?php

namespace App\Filament\Resources\Zakazkas\Tables;

use App\Models\Zakazka;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ZakazkasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cislo')->label('Číslo')->searchable()->sortable(),
                TextColumn::make('zakaznik.jmeno')
                    ->label('Zákazník')
                    ->getStateUsing(fn ($record) => $record->zakaznik?->nazev)
                    ->searchable(['zakaznici.jmeno', 'zakaznici.firma_nazev']),
                TextColumn::make('zarizeni.oznaceni')->label('Zařízení')->searchable()->toggleable(),
                TextColumn::make('stav')
                    ->label('Stav')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Zakazka::STAVY[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'prijato' => 'gray',
                        'diagnostika' => 'info',
                        'ceka_na_dil' => 'warning',
                        'hotovo' => 'success',
                        'vydano' => 'success',
                        'nerentabilni' => 'danger',
                        'storno' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('datum_prijeti')->label('Přijato')->date('d.m.Y')->sortable(),
                TextColumn::make('datum_vyrizeni')->label('Vyřízeno')->date('d.m.Y')->sortable()->toggleable(),
                TextColumn::make('reklamaceK.cislo')
                    ->label('Reklamace k')
                    ->badge()->color('danger')->icon('heroicon-o-arrow-uturn-left')
                    ->placeholder('—')
                    ->url(fn ($record) => $record->reklamace_k_id
                        ? \App\Filament\Resources\Zakazkas\ZakazkaResource::getUrl('edit', ['record' => $record->reklamace_k_id])
                        : null)
                    ->toggleable(),
                TextColumn::make('zaruka_do')
                    ->label('Záruka do')
                    ->state(fn (Zakazka $record) => $record->zarukaDo())
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->color(fn (Zakazka $record) => $record->vZaruce() ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('dil_objednany')->label('Díl obj.')
                    ->boolean()
                    ->trueIcon('heroicon-o-truck')->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')->falseColor('gray')
                    ->tooltip(fn ($record) => $record->dil_info)
                    ->toggleable(),
                IconColumn::make('sken_dokladu')->label('Sken')
                    ->state(fn ($record) => filled($record->sken_dokladu))
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')->falseIcon('heroicon-o-minus')
                    ->trueColor('success')->falseColor('gray')
                    ->toggleable(),
                TextColumn::make('cena_celkem')->label('Cena')->money('CZK')->sortable(),
            ])
            ->filters([
                SelectFilter::make('stav')->label('Stav')->options(Zakazka::STAVY),
                TernaryFilter::make('dil_objednany')->label('Náhradní díl objednán'),
                TernaryFilter::make('reklamace')
                    ->label('Reklamace')
                    ->placeholder('Vše')
                    ->trueLabel('Jen reklamace')
                    ->falseLabel('Bez reklamací')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('reklamace_k_id'),
                        false: fn ($query) => $query->whereNull('reklamace_k_id'),
                        blank: fn ($query) => $query,
                    ),
                Filter::make('v_zaruce')
                    ->label('Jen v záruce')
                    ->toggle()
                    ->query(fn ($query) => $query
                        ->whereIn('stav', Zakazka::STAVY_ZAPLACENO)
                        ->whereNotNull('datum_vyrizeni')
                        ->whereRaw('DATE_ADD(datum_vyrizeni, INTERVAL COALESCE(zaruka_mesice, 0) MONTH) >= CURDATE()')),
            ])
            ->defaultSort('datum_prijeti', 'desc')
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('stav_diagnostika')->label('Diagnostikováno')->icon('heroicon-o-magnifying-glass')->color('info')
                        ->visible(fn ($record) => ! in_array($record->stav, ['diagnostika', 'hotovo', 'vydano'], true))
                        ->action(fn ($record) => $record->update(['stav' => 'diagnostika'])),
                    Action::make('stav_ceka')->label('Čeká na díl')->icon('heroicon-o-truck')->color('warning')
                        ->visible(fn ($record) => ! in_array($record->stav, ['ceka_na_dil', 'vydano'], true))
                        ->action(fn ($record) => $record->update(['stav' => 'ceka_na_dil'])),
                    Action::make('stav_hotovo')->label('Opraveno')->icon('heroicon-o-check-circle')->color('success')
                        ->visible(fn ($record) => ! in_array($record->stav, ['hotovo', 'vydano'], true))
                        ->action(fn ($record) => $record->update(['stav' => 'hotovo'])),
                    Action::make('uzavrit')->label('Uzavřít a tisk protokolu')->icon('heroicon-o-lock-closed')->color('primary')
                        ->visible(fn ($record) => $record->stav !== 'vydano')
                        ->schema([
                            \Filament\Forms\Components\Radio::make('zpusob_uhrady')->label('Platba')
                                ->options(Zakazka::ZPUSOBY_UHRADY)->default('hotove')->inline()->required(),
                        ])
                        ->modalDescription('Nastaví „Vydáno", zapíše příjem do deníku a otevře servisní protokol.')
                        ->action(function (array $data, $record) {
                            $record->update([
                                'stav' => 'vydano',
                                'zpusob_uhrady' => $data['zpusob_uhrady'],
                                'datum_vyrizeni' => $record->datum_vyrizeni ?? now()->toDateString(),
                            ]);

                            return redirect(route('tisk.zakazka.protokol', $record));
                        }),
                ])->label('Stav')->icon('heroicon-o-arrow-path')->button()->color('gray'),
                ActionGroup::make([
                    Action::make('servisni_doklad')
                        ->label('Doklad o převzetí (PDF)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn ($record) => route('tisk.zakazka.doklad', $record))
                        ->openUrlInNewTab(),
                    Action::make('servisni_protokol')
                        ->label('Servisní protokol (PDF)')
                        ->icon('heroicon-o-document-check')
                        ->url(fn ($record) => route('tisk.zakazka.protokol', $record))
                        ->openUrlInNewTab(),
                ])->label('Tisk')->icon('heroicon-o-printer'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
