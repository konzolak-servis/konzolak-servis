<?php

namespace App\Filament\Resources\Zakazkas;

use App\Filament\Resources\Zakazkas\Pages\CreateZakazka;
use App\Filament\Resources\Zakazkas\Pages\EditZakazka;
use App\Filament\Resources\Zakazkas\Pages\ListZakazkas;
use App\Filament\Resources\Zakazkas\RelationManagers\PolozkyRelationManager;
use App\Filament\Resources\Zakazkas\Schemas\ZakazkaForm;
use App\Filament\Resources\Zakazkas\Tables\ZakazkasTable;
use App\Models\Zakazka;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ZakazkaResource extends Resource
{
    protected static ?string $model = Zakazka::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Servis';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Zakázky';

    protected static ?string $modelLabel = 'zakázka';

    protected static ?string $pluralModelLabel = 'Zakázky';

    protected static ?string $recordTitleAttribute = 'cislo';

    public static function form(Schema $schema): Schema
    {
        return ZakazkaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ZakazkasTable::configure($table);
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
            'index' => ListZakazkas::route('/'),
            'create' => CreateZakazka::route('/create'),
            'edit' => EditZakazka::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereNotIn('stav', ['vydano', 'storno'])->count();
    }
}
