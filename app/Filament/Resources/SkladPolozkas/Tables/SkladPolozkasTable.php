<?php

namespace App\Filament\Resources\SkladPolozkas\Tables;

use App\Support\Platformy;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SkladPolozkasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->select('sklad_polozky.*')->selectSub(
                \Illuminate\Support\Facades\DB::table('zakazka_polozky')->selectRaw('count(*)')
                    ->whereColumn('zakazka_polozky.sklad_polozka_id', 'sklad_polozky.id'),
                'pouzito')
                ->where(fn ($q) => $q->where('kategorie', '!=', 'Bazar')->orWhereNull('kategorie')))
            ->columns([
                TextColumn::make('nazev')->label('Název')->searchable()->wrap(),
                TextColumn::make('platforma')->label('Platforma')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => Platformy::label($state))
                    ->placeholder('—')->toggleable(),
                TextColumn::make('kategorie')->label('Typ dílu')->badge()->searchable()->toggleable(),
                TextColumn::make('mnozstvi_skladem')
                    ->label('Skladem')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record->spotrebni
                        ? mb_strtolower(\App\Models\SkladPolozka::DRUHY[$record->druh] ?? 'bez evidence')
                        : $state)
                    ->color(fn ($record) => $record->spotrebni ? 'gray' : ($record->pod_minimem ? 'danger' : 'success')),
                TextColumn::make('pouzito')->label('Použito ×')->sortable()->badge()->color('gray')
                    ->tooltip('Kolikrát byl díl použit v zakázkách – seřaď podle sloupce pro nejčastější.'),
                TextColumn::make('min_mnozstvi')->label('Min.')->numeric()->toggleable(),
                TextColumn::make('cena_ks_prumer')->label('Cena/ks Ø')->money('CZK')->sortable(),
                TextColumn::make('umisteni')->label('Umístění')->searchable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('platforma')->label('Platforma')->options(Platformy::HODNOTY),
                Filter::make('pod_minimem')
                    ->label('Jen pod minimem')
                    ->query(fn ($query) => $query
                        ->whereColumn('mnozstvi_skladem', '<=', 'min_mnozstvi')
                        ->where('min_mnozstvi', '>', 0)),
            ])
            ->defaultSort('nazev')
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('na_zapujcni_sklad')
                    ->label('Na zápůjční sklad')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->visible(fn ($record) => (float) $record->mnozstvi_skladem >= 1)
                    ->requiresConfirmation()
                    ->modalDescription(fn ($record) => 'Ze skladu se odepíše 1 ks „' . $record->nazev . '" a přidá se na zápůjční sklad, kde bude sloužit jen k zapůjčování, dokud ho nevrátíš zpět.')
                    ->action(function ($record) {
                        $d = \App\Models\ZapujcniZarizeni::zeSkladu($record);
                        \Filament\Notifications\Notification::make()->success()->title('Přidáno na zápůjční sklad')
                            ->body('Doplň sériové číslo, příslušenství, kauci a ceník a vytiskni identifikační štítek.')
                            ->actions([\Filament\Actions\Action::make('otevrit')->label('Otevřít')->button()
                                ->url(\App\Filament\Resources\ZapujcniZarizenis\ZapujcniZarizeniResource::getUrl('edit', ['record' => $d]))])
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
