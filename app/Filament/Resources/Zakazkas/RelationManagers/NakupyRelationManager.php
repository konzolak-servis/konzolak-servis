<?php

namespace App\Filament\Resources\Zakazkas\RelationManagers;

use App\Models\NakupPolozka;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Přehled dílů koupených pro tuto zakázku (jen ke čtení, informativní náklad). */
class NakupyRelationManager extends RelationManager
{
    protected static string $relationship = 'nakupPolozky';

    protected static ?string $title = 'Nakoupené díly';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-shopping-cart';

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->nakupPolozky()->exists();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nazev')
            ->columns([
                TextColumn::make('nazev')->label('Díl')->wrap(),
                TextColumn::make('nakup.dodavatel')->label('Dodavatel')->badge()->placeholder('—'),
                TextColumn::make('nakup.datum')->label('Datum')->date('d.m.Y')->placeholder('—'),
                TextColumn::make('mnozstvi_ks')->label('Ks')->numeric(),
                TextColumn::make('castka_celkem')->label('Náklad')->money('CZK')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('CZK')->label('Náklady celkem')),
                TextColumn::make('nakup.cislo')->label('Doklad nákupu')->badge()
                    ->url(fn (NakupPolozka $r) => $r->nakup
                        ? \App\Filament\Resources\Nakups\NakupResource::getUrl('edit', ['record' => $r->nakup_id])
                        : null),
            ])
            ->paginated(false);
    }
}
