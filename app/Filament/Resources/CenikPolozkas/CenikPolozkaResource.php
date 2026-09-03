<?php

namespace App\Filament\Resources\CenikPolozkas;

use App\Filament\Resources\CenikPolozkas\Pages\CreateCenikPolozka;
use App\Filament\Resources\CenikPolozkas\Pages\EditCenikPolozka;
use App\Filament\Resources\CenikPolozkas\Pages\ListCenikPolozkas;
use App\Filament\Resources\CenikPolozkas\Schemas\CenikPolozkaForm;
use App\Filament\Resources\CenikPolozkas\Tables\CenikPolozkasTable;
use App\Models\CenikPolozka;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CenikPolozkaResource extends Resource
{
    protected static ?string $model = CenikPolozka::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Nastavení';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Ceník úkonů';

    protected static ?string $modelLabel = 'položka ceníku';

    protected static ?string $pluralModelLabel = 'Ceník úkonů';

    public static function form(Schema $schema): Schema
    {
        return CenikPolozkaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CenikPolozkasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCenikPolozkas::route('/'),
            'create' => CreateCenikPolozka::route('/create'),
            'edit' => EditCenikPolozka::route('/{record}/edit'),
        ];
    }
}
