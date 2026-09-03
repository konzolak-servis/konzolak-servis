<?php

namespace App\Filament\Resources\Sablonas;

use App\Filament\Resources\Sablonas\Pages\CreateSablona;
use App\Filament\Resources\Sablonas\Pages\EditSablona;
use App\Filament\Resources\Sablonas\Pages\ListSablonas;
use App\Filament\Resources\Sablonas\Schemas\SablonaForm;
use App\Filament\Resources\Sablonas\Tables\SablonasTable;
use App\Models\Sablona;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SablonaResource extends Resource
{
    protected static ?string $model = Sablona::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Nastavení';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Šablony textů';

    protected static ?string $modelLabel = 'šablona';

    protected static ?string $pluralModelLabel = 'Šablony textů';

    public static function form(Schema $schema): Schema
    {
        return SablonaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SablonasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSablonas::route('/'),
            'create' => CreateSablona::route('/create'),
            'edit' => EditSablona::route('/{record}/edit'),
        ];
    }
}
