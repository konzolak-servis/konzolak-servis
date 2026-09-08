<?php

namespace App\Filament\Resources\Nakups\Tables;

use App\Models\Nakup;
use App\Models\ObjednavkaDilu;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class NakupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cislo')->label('Číslo')->searchable()->sortable(),
                TextColumn::make('datum')->label('Datum')->date('d.m.Y')->sortable(),
                TextColumn::make('dodavatel')->label('Dodavatel')->badge()->searchable(),
                TextColumn::make('polozky_count')->label('Položek')->counts('polozky'),
                TextColumn::make('postovne')->label('Poštovné')->money('CZK')->toggleable()->placeholder('—'),
                TextColumn::make('celkem')->label('Celkem')->money('CZK')->sortable(),
                IconColumn::make('doklad_soubor')->label('Doklad')
                    ->state(fn ($record) => filled($record->doklad_soubor))
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')->falseIcon('heroicon-o-minus')
                    ->trueColor('success')->falseColor('gray'),
                IconColumn::make('naskladneno')->label('Naskladněno')->boolean(),
                IconColumn::make('preneseno_do_objednavek')->label('V objednávkách')->boolean()
                    ->trueIcon('heroicon-o-inbox-arrow-down')->falseIcon('heroicon-o-minus')
                    ->trueColor('info')->falseColor('gray')->toggleable(),
            ])
            ->defaultSort('datum', 'desc')
            ->filters([
                TernaryFilter::make('naskladneno')->label('Naskladněno')
                    ->placeholder('Vše')->trueLabel('Naskladněné')->falseLabel('Čeká na naskladnění'),
            ])
            ->recordActions([
                Action::make('naskladnit')
                    ->label('Naskladnit')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->naskladneno && $record->polozky()->exists())
                    ->requiresConfirmation()
                    ->modalDescription('Přidá kusy na sklad v ceně podle položky (bez poštovného), přepočítá vážený průměr a do peněžního deníku zapíše výdaj = položky + poštovné. Nelze vzít zpět.')
                    ->action(fn ($record) => $record->naskladnit()),
                Action::make('do_objednavek')
                    ->label('Vytvořit objednávky dílů')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('gray')
                    ->visible(fn (Nakup $record) => $record->polozky()->exists() && ! $record->preneseno_do_objednavek)
                    ->requiresConfirmation()
                    ->modalHeading('Přenést položky nákupu do Objednávek dílů')
                    ->modalDescription('Z každé položky nákupu vznikne záznam v Objednávkách dílů (nové číslo řady OBJ, stav „Dorazilo", datum doručení = datum nákupu, dodavatel a případná zakázka se přenesou). Účetně nic navíc nevzniká – Objednávka dílu je jen interní sledovník, do peněžního deníku ani daní nejde. Nákup (daňový doklad) zůstává beze změny. Po přenesení tato akce zmizí.')
                    ->modalSubmitActionLabel('Přenést')
                    ->action(fn (Nakup $record) => self::doObjednavek($record)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Vytvoří z položek nákupu objednávky dílů (s vlastní číselnou řadou OBJ). */
    private static function doObjednavek(Nakup $nakup): void
    {
        $vytvoreno = 0;
        $preskoceno = 0;
        $znacka = 'Z nákupu ' . $nakup->cislo;

        foreach ($nakup->polozky as $p) {
            $jizExistuje = ObjednavkaDilu::where('poznamka', 'like', '%' . $znacka . '%')
                ->where('nazev_dilu', $p->nazev)
                ->exists();

            if ($jizExistuje) {
                $preskoceno++;

                continue;
            }

            ObjednavkaDilu::create([
                'dodavatel' => $nakup->dodavatel,
                'datum_objednavky' => $nakup->datum?->toDateString() ?? now()->toDateString(),
                'doruceno_datum' => $nakup->datum?->toDateString() ?? now()->toDateString(),
                'nazev_dilu' => $p->nazev,
                'mnozstvi' => $p->mnozstvi_ks,
                'cena_odhad' => $p->castka_celkem,
                'stav' => 'dorazilo',
                'zakazka_id' => $p->zakazka_id,
                'poznamka' => $znacka,
            ]);
            $vytvoreno++;
        }

        $nakup->update(['preneseno_do_objednavek' => true]);

        Notification::make()
            ->title($vytvoreno > 0
                ? "Přeneseno do Objednávek dílů ({$vytvoreno})" . ($preskoceno ? " – {$preskoceno} už existovalo" : '')
                : 'Vše už bylo přeneseno')
            ->success()
            ->send();
    }
}
