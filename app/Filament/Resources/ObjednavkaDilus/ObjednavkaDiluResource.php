<?php

namespace App\Filament\Resources\ObjednavkaDilus;

use App\Filament\Resources\ObjednavkaDilus\Pages\CreateObjednavkaDilu;
use App\Filament\Resources\ObjednavkaDilus\Pages\EditObjednavkaDilu;
use App\Filament\Resources\ObjednavkaDilus\Pages\ListObjednavkaDilus;
use App\Filament\Resources\ObjednavkaDilus\Schemas\ObjednavkaDiluForm;
use App\Filament\Resources\ObjednavkaDilus\Tables\ObjednavkaDilusTable;
use App\Models\ObjednavkaDilu;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ObjednavkaDiluResource extends Resource
{
    protected static ?string $model = ObjednavkaDilu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Sklad';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Objednávky dílů';

    protected static ?string $modelLabel = 'objednávka dílu';

    protected static ?string $pluralModelLabel = 'Objednávky dílů';

    protected static ?string $recordTitleAttribute = 'nazev_dilu';

    public static function form(Schema $schema): Schema
    {
        return ObjednavkaDiluForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObjednavkaDilusTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObjednavkaDilus::route('/'),
            'create' => CreateObjednavkaDilu::route('/create'),
            'edit' => EditObjednavkaDilu::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (static::getModel()::where('stav', 'objednano')->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
