<?php

namespace App\Filament\Resources\Zpravas\Tables;

use App\Models\Zprava;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ZpravasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('smer')
                    ->label('')
                    ->icon(fn (string $state) => $state === 'in' ? 'heroicon-o-arrow-down-left' : 'heroicon-o-arrow-up-right')
                    ->color(fn (string $state) => $state === 'in' ? 'primary' : 'gray'),
                TextColumn::make('od')
                    ->label('Od / komu')
                    ->formatStateUsing(fn (Zprava $r) => $r->smer === 'in' ? ($r->od_jmeno ?: $r->od) : $r->pro)
                    ->description(fn (Zprava $r) => $r->smer === 'in' ? $r->od : null)
                    ->searchable(['od', 'od_jmeno', 'pro'])
                    ->weight(fn (Zprava $r) => $r->smer === 'in' && ! $r->jePrectena() ? 'bold' : null),
                TextColumn::make('predmet')
                    ->label('Předmět')
                    ->description(fn (Zprava $r) => $r->nahled(80))
                    ->searchable()
                    ->wrap()
                    ->weight(fn (Zprava $r) => $r->smer === 'in' && ! $r->jePrectena() ? 'bold' : null),
                TextColumn::make('zakazka.cislo')
                    ->label('Zakázka')
                    ->badge()
                    ->url(fn (Zprava $r) => $r->zakazka
                        ? \App\Filament\Resources\Zakazkas\ZakazkaResource::getUrl('edit', ['record' => $r->zakazka_id])
                        : null)
                    ->placeholder('—'),
                TextColumn::make('schranka')->label('Schránka')->toggleable()->badge()->color('gray'),
                TextColumn::make('datum')->label('Datum')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('smer')->label('Směr')->options(['in' => 'Přijaté', 'out' => 'Odeslané']),
                SelectFilter::make('schranka')->label('Schránka')
                    ->options(fn () => Zprava::query()->whereNotNull('schranka')->distinct()
                        ->pluck('schranka', 'schranka')->all()),
                TernaryFilter::make('precteno_at')->label('Přečtení')
                    ->placeholder('Vše')->trueLabel('Přečtené')->falseLabel('Nepřečtené')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('precteno_at'),
                        false: fn ($q) => $q->whereNull('precteno_at'),
                        blank: fn ($q) => $q,
                    ),
            ])
            ->defaultSort('datum', 'desc')
            ->recordActions([
                ViewAction::make()->label('Otevřít'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
