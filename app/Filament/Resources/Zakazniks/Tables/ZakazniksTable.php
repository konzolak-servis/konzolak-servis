<?php

namespace App\Filament\Resources\Zakazniks\Tables;

use App\Models\Zakaznik;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ZakazniksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nazev')
                    ->label('Zákazník')
                    ->getStateUsing(fn ($record) => $record->nazev)
                    ->searchable(query: fn ($query, string $search) => $query->whereKey(array_keys(Zakaznik::hledej($search, 500))))
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('jmeno', $direction)),
                TextColumn::make('typ')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'firma' ? 'Firma' : 'Osoba'),
                TextColumn::make('telefon')->label('Telefon')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable()->toggleable(),
                TextColumn::make('mesto')->label('Město')->searchable()->toggleable(),
                TextColumn::make('zakazky_count')->label('Zakázek')->counts('zakazky')->badge()->sortable(),
                TextColumn::make('created_at')->label('Vytvořen')->date('d.m.Y')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('typ')->label('Typ')
                    ->options(['osoba' => 'Osoba', 'firma' => 'Firma']),
            ])
            ->defaultSort('jmeno')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('sloucit')
                        ->label('Sloučit vybrané')
                        ->icon('heroicon-o-arrows-pointing-in')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription('Ponechá se zákazník s nejvíc zakázkami, ostatní se do něj sloučí (zakázky, zařízení, faktury, nabídky, zprávy, chybějící údaje) a smažou se.')
                        ->action(function ($records) {
                            if ($records->count() < 2) {
                                \Filament\Notifications\Notification::make()->warning()->title('Vyber aspoň 2 zákazníky')->send();

                                return;
                            }
                            $hlavni = $records->sortByDesc(fn ($z) => $z->zakazky()->count())->first();
                            $hlavni->sloucit($records->except($hlavni->id));
                            \Filament\Notifications\Notification::make()->success()->title('Sloučeno do: ' . $hlavni->nazev)->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
