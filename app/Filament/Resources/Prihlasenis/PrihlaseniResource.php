<?php

namespace App\Filament\Resources\Prihlasenis;

use App\Filament\Concerns\JenProAdmina;
use App\Filament\Resources\Prihlasenis\Pages\ListPrihlasenis;
use App\Filament\Resources\Prihlasenis\Tables\PrihlasenisTable;
use App\Models\Prihlaseni;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PrihlaseniResource extends Resource
{
    use JenProAdmina;

    protected static ?string $model = Prihlaseni::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Nastavení';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Přihlášení';

    protected static ?string $modelLabel = 'přihlášení';

    protected static ?string $pluralModelLabel = 'Přihlášení';

    public static function table(Table $table): Table
    {
        return PrihlasenisTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrihlasenis::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
