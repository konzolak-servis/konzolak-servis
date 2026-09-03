<?php

namespace App\Filament\Resources\Pristups\Tables;

use App\Models\Pristup;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PristupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nazev')->label('Název')->searchable()->weight('bold'),
                TextColumn::make('kategorie')->label('Kategorie')->badge()
                    ->formatStateUsing(fn ($s) => Pristup::KATEGORIE[$s] ?? $s),
                TextColumn::make('uzivatel')->label('Login')->searchable()->copyable()->toggleable(),
                TextColumn::make('url')->label('Adresa')
                    ->url(fn ($record) => $record->url)->openUrlInNewTab()
                    ->formatStateUsing(fn ($state) => $state ? 'otevřít ↗' : '')
                    ->color('primary')->toggleable(),
                TextColumn::make('platnost_do')->label('Platnost / splatnost')->date('d.m.Y')->sortable()
                    ->description(fn (Pristup $r) => match (true) {
                        $r->dniDoKonce() === null => null,
                        $r->dniDoKonce() < 0 => 'po termínu o ' . abs($r->dniDoKonce()) . ' dní',
                        default => 'za ' . $r->dniDoKonce() . ' dní',
                    })
                    ->color(fn (Pristup $r) => $r->dniDoKonce() === null ? null
                        : ($r->jeNaSpadnuti() ? 'danger' : 'success')),
                TextColumn::make('castka')->label('Poplatek')->money('CZK')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('kategorie')->label('Kategorie')->options(Pristup::KATEGORIE),
            ])
            ->defaultSort('platnost_do')
            ->recordActions([
                Action::make('heslo')
                    ->label('Heslo')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->visible(fn (Pristup $r) => filled($r->heslo))
                    ->modalHeading(fn (Pristup $r) => 'Heslo – ' . $r->nazev)
                    ->modalContent(fn (Pristup $r) => new \Illuminate\Support\HtmlString(
                        '<div style="font-family:monospace;font-size:1.1rem;padding:.75rem 1rem;border-radius:.5rem;'
                        . 'background:rgba(200,153,46,.12);user-select:all;word-break:break-all">'
                        . e((string) $r->heslo) . '</div>'
                        . '<p style="margin-top:.5rem;font-size:.8rem;color:#6b7280">Označ a zkopíruj.</p>'
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Zavřít'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
