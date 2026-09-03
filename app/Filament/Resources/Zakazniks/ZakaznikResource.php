<?php

namespace App\Filament\Resources\Zakazniks;

use App\Filament\Resources\Zakazniks\Pages\CreateZakaznik;
use App\Filament\Resources\Zakazniks\Pages\EditZakaznik;
use App\Filament\Resources\Zakazniks\Pages\ListZakazniks;
use App\Filament\Resources\Zakazniks\Schemas\ZakaznikForm;
use App\Filament\Resources\Zakazniks\Tables\ZakazniksTable;
use App\Models\Zakaznik;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ZakaznikResource extends Resource
{
    protected static ?string $model = Zakaznik::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Servis';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Zákazníci';

    protected static ?string $modelLabel = 'zákazník';

    protected static ?string $pluralModelLabel = 'Zákazníci';

    protected static ?string $recordTitleAttribute = 'jmeno';

    public static function form(Schema $schema): Schema
    {
        return ZakaznikForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ZakazniksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ZarizeniRelationManager::class,
            RelationManagers\ZakazkyRelationManager::class,
        ];
    }

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): ?string
    {
        return $record?->nazev;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZakazniks::route('/'),
            'create' => CreateZakaznik::route('/create'),
            'edit' => EditZakaznik::route('/{record}/edit'),
        ];
    }
}
