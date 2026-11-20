<?php

namespace App\Filament\Resources\EetTrzbas;

use App\Models\EetTrzba;
use App\Support\Eet\Eet;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Přehled tržeb odeslaných do EET 2.0 (jen čtení; vznikají automaticky z peněžního deníku). */
class EetTrzbaResource extends Resource
{
    protected static ?string $model = EetTrzba::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'EET – evidence tržeb';

    protected static ?string $modelLabel = 'evidovaná tržba';

    protected static ?string $pluralModelLabel = 'EET – evidované tržby';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $n = EetTrzba::where('stav', '!=', 'odeslano')->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Zaznamenáno')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('popis')->label('Příjem')->wrap()->searchable()
                    ->description(fn (EetTrzba $r) => 'č. ' . $r->porad_cis . ' · ' . \Illuminate\Support\Str::of($r->dat_trzby)->replace('T', ' ')->before('+')),
                TextColumn::make('celk_trzba')->label('Částka')->money('CZK')->color(fn (EetTrzba $r) => $r->storno ? 'danger' : null),
                TextColumn::make('stav')->label('Stav')->badge()
                    ->formatStateUsing(fn ($state, EetTrzba $r) => (EetTrzba::STAVY[$state] ?? $state) . ($r->overeni ? ' (ověřovací)' : ''))
                    ->color(fn ($state) => match ($state) { 'odeslano' => 'success', 'chyba' => 'danger', default => 'warning' })
                    ->description(fn (EetTrzba $r) => $r->stav === 'odeslano'
                        ? ($r->prostredi === 'produkce' ? '' : 'testovací prostředí · ') . 'POK ' . $r->pok
                        : trim(($r->chyba_kod !== null ? '[' . $r->chyba_kod . '] ' : '') . $r->chyba_text . ($r->pokusy ? ' · pokusů ' . $r->pokusy : ''))),
                TextColumn::make('varovani')->label('Varování')->toggleable()
                    ->formatStateUsing(fn ($state, EetTrzba $r) => collect($r->varovani)->pluck('text')->implode('; '))->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('stav')->label('Stav')->options(EetTrzba::STAVY),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('odeslat')->label('Odeslat znovu')->icon('heroicon-o-arrow-path')->button()
                    ->visible(fn (EetTrzba $r) => $r->stav !== 'odeslano')
                    ->action(function (EetTrzba $r) {
                        $r = Eet::odeslat($r);
                        $r->stav === 'odeslano'
                            ? Notification::make()->success()->title('Odesláno, POK ' . $r->pok)->send()
                            : Notification::make()->danger()->title('Neodesláno')->body($r->chyba_text)->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEetTrzbas::route('/'),
        ];
    }
}
