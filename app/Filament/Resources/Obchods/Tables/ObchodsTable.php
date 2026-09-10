<?php

namespace App\Filament\Resources\Obchods\Tables;

use App\Models\Obchod;
use App\Support\Platformy;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ObchodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cislo')->label('Číslo')->searchable()->sortable(),
                TextColumn::make('datum')->label('Nakoupeno')->date('d.m.Y')->sortable(),
                TextColumn::make('kategorie')->label('Platforma')->badge()
                    ->formatStateUsing(fn ($state) => Platformy::label($state))->toggleable(),
                TextColumn::make('nazev')->label('Zařízení')->searchable()->wrap(),
                TextColumn::make('cena')->label('Nákup')->money('CZK')->sortable(),
                TextColumn::make('naklady_dilu')->label('Díly / náklady')->money('CZK')
                    ->state(fn (Obchod $r) => $r->naklady_dilu)->placeholder('—'),
                TextColumn::make('prodejni_cena')->label('Prodej')->money('CZK')->placeholder('—')->sortable(),
                TextColumn::make('zisk')->label('Zisk')
                    ->state(fn (Obchod $r) => $r->zisk)
                    ->money('CZK')->placeholder('—')
                    ->color(fn (?float $state) => $state === null ? 'gray' : ($state >= 0 ? 'success' : 'danger'))
                    ->weight('bold'),
                TextColumn::make('prodano')->label('Stav')->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Prodáno' : 'Skladem')
                    ->color(fn ($state) => $state ? 'success' : 'warning'),
            ])
            ->filters([
                TernaryFilter::make('prodano')->label('Stav')
                    ->placeholder('Vše')->trueLabel('Prodané')->falseLabel('Skladem'),
                SelectFilter::make('kategorie')->label('Platforma')->options(Platformy::HODNOTY),
            ])
            ->defaultSort('datum', 'desc')
            ->recordActions([
                Action::make('prodat')
                    ->label('Prodat')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Obchod $record) => ! $record->prodano)
                    ->schema([
                        TextInput::make('prodejni_cena')->label('Prodejní cena')->numeric()->required()->suffix('Kč'),
                        DatePicker::make('prodej_datum')->label('Datum prodeje')->default(now())->native(false)->required(),
                        TextInput::make('prodej_komu')->label('Komu (nepovinné)'),
                    ])
                    ->modalDescription(fn (Obchod $record) => 'Investováno celkem: '.number_format($record->naklady_celkem, 0, ',', ' ').' Kč (nákup '.number_format((float) $record->cena, 0, ',', ' ').' + díly '.number_format($record->naklady_dilu, 0, ',', ' ').'). Bazar je interní – do peněžního deníku se nic nezapisuje.')
                    ->action(function (array $data, Obchod $record) {
                        $record->prodat((float) $data['prodejni_cena'], $data['prodej_datum'], $data['prodej_komu'] ?? null);

                        Notification::make()
                            ->title('Prodáno za '.number_format((float) $data['prodejni_cena'], 0, ',', ' ').' Kč')
                            ->body($record->zisk !== null
                                ? 'Zisk: '.number_format($record->zisk, 0, ',', ' ').' Kč'
                                : null)
                            ->success()
                            ->actions([
                                Action::make('doklad_prodej')
                                    ->label('Otevřít doklad o prodeji (PDF)')
                                    ->icon('heroicon-o-document-check')
                                    ->url(route('tisk.obchod', ['obchod' => $record, 'typ' => 'prodej']), shouldOpenInNewTab: true)
                                    ->button(),
                            ])
                            ->persistent()
                            ->send();
                    }),
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('doklad_vykup')
                        ->label('Doklad o výkupu (PDF)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (Obchod $record) => route('tisk.obchod', $record))
                        ->openUrlInNewTab(),
                    Action::make('doklad_prodej')
                        ->label('Doklad o prodeji (PDF)')
                        ->icon('heroicon-o-document-check')
                        ->visible(fn (Obchod $record) => $record->prodano)
                        ->url(fn (Obchod $record) => route('tisk.obchod', ['obchod' => $record, 'typ' => 'prodej']))
                        ->openUrlInNewTab(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
