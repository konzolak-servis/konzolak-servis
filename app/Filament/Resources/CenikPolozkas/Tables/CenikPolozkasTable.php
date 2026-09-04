<?php

namespace App\Filament\Resources\CenikPolozkas\Tables;

use App\Models\CenikPolozka;
use App\Support\Platformy;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CenikPolozkasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextInputColumn::make('poradi')
                    ->label('#')
                    ->type('number')
                    ->rules(['numeric'])
                    ->sortable()
                    ->width('1%'),
                TextColumn::make('kategorie')->label('Platforma / kategorie')->badge()->sortable()
                    ->formatStateUsing(fn ($state) => Platformy::label($state)),
                TextColumn::make('nazev')->label('Název úkonu')->searchable()->wrap(),
                TextColumn::make('cena')->label('Cena')->money('CZK')->sortable(),
                IconColumn::make('aktivni')->label('Aktivní')->boolean(),
            ])
            ->filters([
                SelectFilter::make('kategorie')->label('Platforma / kategorie')->options(Platformy::HODNOTY),
            ])
            ->defaultSort('poradi')
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption('all')
            ->recordActions([
                EditAction::make(),
                Action::make('kopirovat')
                    ->label('Kopírovat do…')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->schema([
                        CheckboxList::make('platformy')
                            ->label('Zkopírovat tento úkon do platforem')
                            ->options(Platformy::HODNOTY)
                            ->columns(2)
                            ->required(),
                    ])
                    ->action(function (array $data, CenikPolozka $record) {
                        $n = self::zkopiruj([$record], $data['platformy']);
                        Notification::make()
                            ->title($n > 0 ? "Zkopírováno ({$n}× vytvořeno)" : 'Nic nezkopírováno – už existuje')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('kopirovat_hromadne')
                        ->label('Kopírovat do platforem')
                        ->icon('heroicon-o-document-duplicate')
                        ->schema([
                            CheckboxList::make('platformy')
                                ->label('Zkopírovat vybrané úkony do platforem')
                                ->options(Platformy::HODNOTY)
                                ->columns(2)
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records) {
                            $n = self::zkopiruj($records, $data['platformy']);
                            Notification::make()
                                ->title($n > 0 ? "Zkopírováno ({$n}× vytvořeno)" : 'Nic nezkopírováno – vše už existuje')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Vytvoří kopie úkonů do cílových platforem.
     * Přeskočí platformu, kde už úkon se stejným názvem existuje.
     */
    private static function zkopiruj(iterable $polozky, array $platformy): int
    {
        $vytvoreno = 0;

        foreach ($polozky as $p) {
            foreach ($platformy as $plat) {
                if ($plat === $p->kategorie) {
                    continue;
                }
                if (CenikPolozka::where('kategorie', $plat)->where('nazev', $p->nazev)->exists()) {
                    continue;
                }

                CenikPolozka::create([
                    'kategorie' => $plat,
                    'nazev' => $p->nazev,
                    'cena' => $p->cena,
                    'poradi' => (int) CenikPolozka::where('kategorie', $plat)->max('poradi') + 1,
                    'aktivni' => $p->aktivni,
                ]);
                $vytvoreno++;
            }
        }

        return $vytvoreno;
    }
}
