<?php

namespace App\Filament\Resources\Zapujckas\Tables;

use App\Models\Zapujcka;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ZapujckasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cislo')->label('Číslo')->searchable()->sortable(),
                TextColumn::make('zakaznik.jmeno')->label('Zákazník')
                    ->getStateUsing(fn ($record) => $record->zakaznik?->nazev)
                    ->searchable(['zakaznici.jmeno', 'zakaznici.firma_nazev']),
                TextColumn::make('zarizeni')->label('Zařízení')->wrap()
                    ->getStateUsing(fn (Zapujcka $r) => $r->polozky->pluck('nazev')->implode(', ')),
                TextColumn::make('datum_zapujceni')->label('Zapůjčeno')->date('d.m.Y')->sortable(),
                TextColumn::make('vratit_do')->label('Vrátit do')->date('d.m.Y')->sortable()->placeholder('—')
                    ->color(fn (Zapujcka $r) => $r->jePoTerminu() ? 'danger' : null)
                    ->description(fn (Zapujcka $r) => $r->stav === 'vraceno' && $r->datum_vraceni
                        ? '✓ vráceno ' . $r->datum_vraceni->format('d.m.Y') : null),
                TextColumn::make('kauce')->label('Kauce')->money('CZK')->sortable()
                    ->description(fn (Zapujcka $r) => (float) $r->kauce_srazka > 0
                        ? 'sraženo ' . number_format((float) $r->kauce_srazka, 0, ',', ' ') . ' Kč' : null),
                TextColumn::make('zakazka.cislo')->label('Zakázka')->badge()->color('gray')->placeholder('—')->toggleable(),
                TextColumn::make('stav')->label('Stav')->badge()
                    ->formatStateUsing(fn ($state) => Zapujcka::STAVY[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'zapujceno' => 'warning',
                        'vraceno' => 'success',
                        default => 'gray',
                    }),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with(['polozky', 'zakaznik', 'zakazka']))
            ->filters([
                SelectFilter::make('stav')->label('Stav')->options(Zapujcka::STAVY)->default('zapujceno'),
            ])
            ->defaultSort('datum_zapujceni', 'desc')
            ->recordActions([
                EditAction::make(),
                Action::make('protokol_btn')->label('Protokol')->icon('heroicon-o-document-text')->color('primary')->button()
                    ->url(fn (Zapujcka $r) => $r->tiskUrl($r->stav === 'vraceno' ? 'vraceni' : 'protokol'))->openUrlInNewTab(),
                ActionGroup::make([
                    Action::make('protokol')->label('Předávací protokol')->icon('heroicon-o-document-text')
                        ->url(fn (Zapujcka $r) => $r->tiskUrl('protokol'))->openUrlInNewTab(),
                    Action::make('kauce')->label('Doklad o přijetí kauce')->icon('heroicon-o-banknotes')
                        ->visible(fn (Zapujcka $r) => (float) $r->kauce > 0)
                        ->url(fn (Zapujcka $r) => $r->tiskUrl('kauce'))->openUrlInNewTab(),
                    Action::make('vyuctovani')->label('Vyúčtování kauce')->icon('heroicon-o-banknotes')
                        ->visible(fn (Zapujcka $r) => $r->stav === 'vraceno')
                        ->url(fn (Zapujcka $r) => $r->tiskUrl('vyuctovani'))->openUrlInNewTab(),
                    Action::make('vraceni')->label('Protokol o vrácení')->icon('heroicon-o-arrow-uturn-left')
                        ->visible(fn (Zapujcka $r) => $r->stav === 'vraceno')
                        ->url(fn (Zapujcka $r) => $r->tiskUrl('vraceni'))->openUrlInNewTab(),
                ])->icon('heroicon-o-printer')->tooltip('Tisk'),
            ]);
    }
}
