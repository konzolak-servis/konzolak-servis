<?php

namespace App\Filament\Resources\SkladPolozkas;

use App\Filament\Resources\SkladPolozkas\Pages\CreateSkladPolozka;
use App\Filament\Resources\SkladPolozkas\Pages\EditSkladPolozka;
use App\Filament\Resources\SkladPolozkas\Pages\ListSkladPolozkas;
use App\Filament\Resources\SkladPolozkas\Schemas\SkladPolozkaForm;
use App\Filament\Resources\SkladPolozkas\Tables\SkladPolozkasTable;
use App\Models\SkladPolozka;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SkladPolozkaResource extends Resource
{
    protected static ?string $model = SkladPolozka::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Sklad';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Skladové položky';

    protected static ?string $modelLabel = 'skladová položka';

    protected static ?string $pluralModelLabel = 'Skladové položky';

    protected static ?string $recordTitleAttribute = 'nazev';

    public static function form(Schema $schema): Schema
    {
        return SkladPolozkaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SkladPolozkasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSkladPolozkas::route('/'),
            'create' => CreateSkladPolozka::route('/create'),
            'edit' => EditSkladPolozka::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pod = static::getModel()::query()
            ->whereColumn('mnozstvi_skladem', '<=', 'min_mnozstvi')
            ->where('min_mnozstvi', '>', 0)->count();

        return $pod ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
