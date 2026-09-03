<?php

namespace App\Filament\Resources\Zakazkas\RelationManagers;

use App\Models\Zakazka;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReklamaceRelationManager extends RelationManager
{
    protected static string $relationship = 'reklamace';

    protected static ?string $title = 'Reklamace';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-arrow-uturn-left';

    /** Panel ukazuj jen tam, kde reklamace dávají smysl. */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->reklamace()->exists()
            || $ownerRecord->jeHotovo()
            || $ownerRecord->stav === 'vydano';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cislo')
            ->columns([
                TextColumn::make('cislo')->label('Číslo')->searchable(),
                TextColumn::make('stav')->label('Stav')->badge()
                    ->formatStateUsing(fn ($state) => Zakazka::STAVY[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'hotovo', 'vydano' => 'success',
                        'nerentabilni', 'storno' => 'danger',
                        'ceka_na_dil' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('popis_zavady')->label('Závada')->limit(60)->wrap(),
                TextColumn::make('datum_prijeti')->label('Přijato')->date('d.m.Y'),
                TextColumn::make('datum_vyrizeni')->label('Vyřízeno')->date('d.m.Y')->placeholder('—'),
            ])
            ->defaultSort('datum_prijeti', 'desc')
            ->emptyStateHeading('Žádné reklamace')
            ->emptyStateDescription('Reklamaci k této zakázce založíš tlačítkem „Další → Založit reklamaci".')
            ->recordUrl(fn ($record) => \App\Filament\Resources\Zakazkas\ZakazkaResource::getUrl('edit', ['record' => $record]));
    }
}
