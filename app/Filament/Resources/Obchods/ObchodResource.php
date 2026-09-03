<?php

namespace App\Filament\Resources\Obchods;

use App\Filament\Resources\Obchods\Pages\CreateObchod;
use App\Filament\Resources\Obchods\Pages\EditObchod;
use App\Filament\Resources\Obchods\Pages\ListObchods;
use App\Filament\Resources\Obchods\Schemas\ObchodForm;
use App\Filament\Resources\Obchods\Tables\ObchodsTable;
use App\Models\Obchod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ObchodResource extends Resource
{
    protected static ?string $model = Obchod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Sklad';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Výkup / prodej';

    protected static ?string $modelLabel = 'výkup / prodej';

    protected static ?string $pluralModelLabel = 'Výkup / prodej';

    protected static ?string $recordTitleAttribute = 'nazev';

    public static function form(Schema $schema): Schema
    {
        return ObchodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObchodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObchods::route('/'),
            'create' => CreateObchod::route('/create'),
            'edit' => EditObchod::route('/{record}/edit'),
        ];
    }
}
