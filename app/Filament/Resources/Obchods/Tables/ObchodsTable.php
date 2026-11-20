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
                    ->formatStateUsing(fn ($state, Obchod $record) => $state ? 'Prodáno'
                        : ($record->jeNaZapujcnimSkladu() ? 'Na zápůjčním skladu' : 'Skladem'))
                    ->color(fn ($state, Obchod $record) => $state ? 'success' : ($record->jeNaZapujcnimSkladu() ? 'info' : 'warning'))
                    ->description(function (Obchod $record) {
                        $p = $record->jeNaZapujcnimSkladu() ? $record->zapujcniZarizeni?->aktualniPolozka() : null;

                        return $p ? 'zapůjčeno: ' . ($p->zapujcka->zakaznik?->nazev ?? $p->zapujcka->cislo) : null;
                    }),
            ])
            ->filters([
                TernaryFilter::make('prodano')->label('Stav')
                    ->placeholder('Vše')->trueLabel('Prodané')->falseLabel('Skladem'),
                SelectFilter::make('kategorie')->label('Platforma')->options(Platformy::HODNOTY),
                \Filament\Tables\Filters\Filter::make('zapujcni')->label('Na zápůjčním skladu')->toggle()
                    ->query(fn ($query) => $query->where('prodano', false)
                        ->whereHas('zapujcniZarizeni', fn ($z) => $z->where('aktivni', true))),
            ])
            ->defaultSort('datum', 'desc')
            ->recordActions([
                Action::make('na_zapujcni_sklad')
                    ->label('Na zápůjční sklad')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->visible(fn (Obchod $record) => ! $record->prodano && ! $record->jeNaZapujcnimSkladu())
                    ->requiresConfirmation()
                    ->modalDescription('Zařízení se přidá do seznamu „Zařízení k zápůjčce" (hodnota = nákup + díly). V bazaru zůstane, jen bude vedené jako na zápůjčním skladu.')
                    ->action(function (Obchod $record) {
                        $d = \App\Models\ZapujcniZarizeni::updateOrCreate(
                            ['obchod_id' => $record->id],
                            [
                                'nazev' => $record->nazev,
                                'seriove_cislo' => $record->seriove_cislo,
                                'stav_popis' => $record->stav_popis ? \Illuminate\Support\Str::limit($record->stav_popis, 250, '') : null,
                                'hodnota' => $record->naklady_celkem ?: $record->cena,
                                'aktivni' => true,
                            ],
                        );
                        Notification::make()->success()->title('Přesunuto na zápůjční sklad')
                            ->body('Doplň v seznamu zařízení k zápůjčce příslušenství a doporučenou kauci.')
                            ->actions([Action::make('otevrit')->label('Otevřít')->button()
                                ->url(\App\Filament\Resources\ZapujcniZarizenis\ZapujcniZarizeniResource::getUrl('edit', ['record' => $d]))])
                            ->send();
                    }),
                Action::make('ze_zapujcniho_skladu')
                    ->label('Vrátit do prodeje')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (Obchod $record) => $record->jeNaZapujcnimSkladu())
                    ->requiresConfirmation()
                    ->modalDescription('Zařízení se přestane nabízet k zápůjčce a v bazaru bude znovu „Skladem".')
                    ->action(function (Obchod $record) {
                        try {
                            $record->zapujcniZarizeni?->prevestZpet();
                        } catch (\RuntimeException $e) {
                            Notification::make()->warning()->title($e->getMessage())->send();
                        }
                    }),
                Action::make('prodat')
                    ->label('Prodat')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Obchod $record) => ! $record->prodano && ! $record->jeNaZapujcnimSkladu())
                    ->schema([
                        TextInput::make('prodejni_cena')->label('Prodejní cena')->numeric()->required()->suffix('Kč'),
                        DatePicker::make('prodej_datum')->label('Datum prodeje')->default(now())->native(false)->required(),
                        TextInput::make('prodej_komu')->label('Komu (nepovinné)'),
                    ])
                    ->modalDescription(fn (Obchod $record) => 'Investováno celkem: '.number_format($record->naklady_celkem, 0, ',', ' ').' Kč (nákup '.number_format((float) $record->cena, 0, ',', ' ').' + díly '.number_format($record->naklady_dilu, 0, ',', ' ').'). Prodejní cena se zapíše jako příjem do peněžního deníku (kategorie „Bazar").')
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
                                    ->url($record->tiskUrl(['typ' => 'prodej']), shouldOpenInNewTab: true)
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
                        ->url(fn (Obchod $record) => $record->tiskUrl())
                        ->openUrlInNewTab(),
                    Action::make('doklad_prodej')
                        ->label('Doklad o prodeji (PDF)')
                        ->icon('heroicon-o-document-check')
                        ->visible(fn (Obchod $record) => $record->prodano)
                        ->url(fn (Obchod $record) => $record->tiskUrl(['typ' => 'prodej']))
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
