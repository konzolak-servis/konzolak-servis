<?php

namespace App\Filament\Resources\Obchods\Tables;

use App\Models\Obchod;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ObchodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cislo')->label('Číslo')->searchable()->sortable(),
                TextColumn::make('typ')->label('Typ')->badge()
                    ->formatStateUsing(fn ($state) => $state === 'vykup' ? 'Výkup' : 'Prodej')
                    ->color(fn ($state) => $state === 'vykup' ? 'warning' : 'success'),
                TextColumn::make('datum')->label('Datum')->date('d.m.Y')->sortable(),
                TextColumn::make('kategorie')->label('Kategorie')->badge()
                    ->formatStateUsing(fn ($state) => Obchod::KATEGORIE[$state] ?? $state),
                TextColumn::make('nazev')->label('Označení')->searchable()->wrap(),
                TextColumn::make('protistrana_jmeno')->label('Protistrana')->searchable()->toggleable(),
                TextColumn::make('cena')->label('Cena')->money('CZK')->sortable(),
                IconColumn::make('vyrizeno')->label('Vyřízeno')->boolean(),
            ])
            ->filters([
                SelectFilter::make('typ')->label('Typ')
                    ->options(['vykup' => 'Výkup', 'prodej' => 'Prodej']),
                SelectFilter::make('kategorie')->label('Kategorie')->options(Obchod::KATEGORIE),
            ])
            ->defaultSort('datum', 'desc')
            ->recordActions([
                Action::make('vyridit')
                    ->label('Vyřídit')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Obchod $record) => ! $record->vyrizeno)
                    ->requiresConfirmation()
                    ->modalDescription(fn (Obchod $record) => $record->typ === 'vykup'
                        ? 'Zapíše výdej peněz a naskladní kus do bazaru. Otevře doklad o výkupu.'
                        : 'Zapíše příjem peněz a odečte kus ze skladu. Otevře doklad o prodeji.')
                    ->action(function (Obchod $record) {
                        $record->vyridit();

                        return redirect(route('tisk.obchod', $record));
                    }),
                Action::make('doklad')
                    ->label('Doklad (PDF)')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Obchod $record) => route('tisk.obchod', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
