<?php

namespace App\Filament\Resources\Nakups;

use App\Filament\Resources\Nakups\Pages\CreateNakup;
use App\Filament\Resources\Nakups\Pages\EditNakup;
use App\Filament\Resources\Nakups\Pages\ListNakups;
use App\Filament\Resources\Nakups\RelationManagers\PolozkyRelationManager;
use App\Filament\Resources\Nakups\Schemas\NakupForm;
use App\Filament\Resources\Nakups\Tables\NakupsTable;
use App\Models\Nakup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NakupResource extends Resource
{
    protected static ?string $model = Nakup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Sklad';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Nákupy';

    protected static ?string $modelLabel = 'nákup';

    protected static ?string $pluralModelLabel = 'Nákupy';

    protected static ?string $recordTitleAttribute = 'cislo';

    public static function form(Schema $schema): Schema
    {
        return NakupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NakupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PolozkyRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNakups::route('/'),
            'create' => CreateNakup::route('/create'),
            'edit' => EditNakup::route('/{record}/edit'),
        ];
    }
}
