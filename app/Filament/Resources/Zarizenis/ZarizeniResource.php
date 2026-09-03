<?php

namespace App\Filament\Resources\Zarizenis;

use App\Filament\Resources\Zarizenis\Pages\CreateZarizeni;
use App\Filament\Resources\Zarizenis\Pages\EditZarizeni;
use App\Filament\Resources\Zarizenis\Pages\ListZarizenis;
use App\Filament\Resources\Zarizenis\Schemas\ZarizeniForm;
use App\Filament\Resources\Zarizenis\Tables\ZarizenisTable;
use App\Models\Zarizeni;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ZarizeniResource extends Resource
{
    // Zařízení se spravují přes zákazníka / zakázku – samostatná položka v menu není potřeba.
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Zarizeni::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static string|UnitEnum|null $navigationGroup = 'Servis';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Zařízení';

    protected static ?string $modelLabel = 'zařízení';

    protected static ?string $pluralModelLabel = 'Zařízení';

    protected static ?string $recordTitleAttribute = 'oznaceni';

    public static function form(Schema $schema): Schema
    {
        return ZarizeniForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ZarizenisTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZarizenis::route('/'),
            'create' => CreateZarizeni::route('/create'),
            'edit' => EditZarizeni::route('/{record}/edit'),
        ];
    }
}
