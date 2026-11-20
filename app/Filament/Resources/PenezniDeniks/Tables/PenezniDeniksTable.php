<?php

namespace App\Filament\Resources\PenezniDeniks\Tables;

use App\Models\PenezniDenik;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PenezniDeniksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('datum')->label('Datum')->date('d.m.Y')->sortable(),
                TextColumn::make('typ')->label('Typ')->badge()
                    ->formatStateUsing(fn ($state) => $state === 'prijem' ? 'Příjem' : 'Výdej')
                    ->color(fn ($state) => $state === 'prijem' ? 'success' : 'danger'),
                TextColumn::make('popis')->label('Popis')->searchable()->wrap(),
                TextColumn::make('doklad')->label('Doklad')
                    ->state(fn (PenezniDenik $record) => $record->doklad()[0] ?: '—')
                    ->url(fn (PenezniDenik $record) => $record->doklad()[1])
                    ->openUrlInNewTab()
                    ->color(fn (PenezniDenik $record) => $record->doklad()[1] ? 'primary' : 'gray')
                    ->weight('bold'),
                TextColumn::make('kategorie')->label('Kategorie')->badge()->toggleable(),
                TextColumn::make('zpusob')->label('Platba')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'hotove' => 'Hotově', 'ucet' => 'Na účet', default => '',
                    })->badge()->toggleable(),
                TextColumn::make('castka')->label('Částka')->money('CZK')->sortable()
                    ->summarize(Sum::make()->label('Součet')->money('CZK')),
                TextColumn::make('eet')->label('EET')->badge()
                    ->state(function (PenezniDenik $record) {
                        $t = $record->eetTrzby->where('storno', false)->sortByDesc('id')->first();
                        if (! $t) {
                            return $record->typ === 'prijem' && \App\Support\Eet\Eet::evidovat($record) ? 'čeká' : null;
                        }

                        return match ($t->stav) { 'odeslano' => 'odesláno', 'chyba' => 'chyba', default => 'čeká' };
                    })
                    ->color(fn ($state) => match ($state) { 'odesláno' => 'success', 'chyba' => 'danger', default => 'warning' })
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('eetTrzby'))
            ->filters([
                SelectFilter::make('typ')->label('Typ')
                    ->options(['prijem' => 'Příjem', 'vydej' => 'Výdej']),
            ])
            ->defaultSort('datum', 'desc')
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('eet_na_miste')
                    ->label('Zaplaceno u mě → EET')
                    ->icon('heroicon-o-qr-code')
                    ->color('warning')
                    ->tooltip('Zákazník zaplatil QR / převodem přímo v provozovně – platbu odeslat do EET')
                    ->visible(fn (PenezniDenik $record) => \App\Support\Eet\Eet::zapnuto() && $record->typ === 'prijem'
                        && $record->zpusob !== 'hotove' && ! $record->eet_na_miste)
                    ->requiresConfirmation()
                    ->modalDescription('Platba převodem přijatá při osobním kontaktu (zákazník platil QR u pultu) je evidovaná tržba. Platbu převodem z domova do EET neposílej.')
                    ->action(fn (PenezniDenik $record) => $record->update(['eet_na_miste' => true])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
