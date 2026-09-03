<?php

namespace App\Filament\Resources\PenezniDeniks;

use App\Filament\Resources\PenezniDeniks\Pages\CreatePenezniDenik;
use App\Filament\Resources\PenezniDeniks\Pages\EditPenezniDenik;
use App\Filament\Resources\PenezniDeniks\Pages\ListPenezniDeniks;
use App\Filament\Resources\PenezniDeniks\Schemas\PenezniDenikForm;
use App\Filament\Resources\PenezniDeniks\Tables\PenezniDeniksTable;
use App\Models\PenezniDenik;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PenezniDenikResource extends Resource
{
    protected static ?string $model = PenezniDenik::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Peněžní deník';

    protected static ?string $modelLabel = 'záznam';

    protected static ?string $pluralModelLabel = 'Peněžní deník';

    public static function form(Schema $schema): Schema
    {
        return PenezniDenikForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PenezniDeniksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPenezniDeniks::route('/'),
            'create' => CreatePenezniDenik::route('/create'),
            'edit' => EditPenezniDenik::route('/{record}/edit'),
        ];
    }
}
