<?php

namespace App\Filament\Resources\KatalogZarizenis;

use App\Filament\Resources\KatalogZarizenis\Pages\CreateKatalogZarizeni;
use App\Filament\Resources\KatalogZarizenis\Pages\EditKatalogZarizeni;
use App\Filament\Resources\KatalogZarizenis\Pages\ListKatalogZarizenis;
use App\Filament\Resources\KatalogZarizenis\Schemas\KatalogZarizeniForm;
use App\Filament\Resources\KatalogZarizenis\Tables\KatalogZarizenisTable;
use App\Models\KatalogZarizeni;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class KatalogZarizeniResource extends Resource
{
    protected static ?string $model = KatalogZarizeni::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Nastavení';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Katalog zařízení';

    protected static ?string $modelLabel = 'model zařízení';

    protected static ?string $pluralModelLabel = 'Katalog zařízení';

    public static function form(Schema $schema): Schema
    {
        return KatalogZarizeniForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KatalogZarizenisTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKatalogZarizenis::route('/'),
            'create' => CreateKatalogZarizeni::route('/create'),
            'edit' => EditKatalogZarizeni::route('/{record}/edit'),
        ];
    }
}
