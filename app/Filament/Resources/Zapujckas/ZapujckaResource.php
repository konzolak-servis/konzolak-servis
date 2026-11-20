<?php

namespace App\Filament\Resources\Zapujckas;

use App\Filament\Resources\Zapujckas\Pages\CreateZapujcka;
use App\Filament\Resources\Zapujckas\Pages\EditZapujcka;
use App\Filament\Resources\Zapujckas\Pages\ListZapujckas;
use App\Filament\Resources\Zapujckas\Schemas\ZapujckaForm;
use App\Filament\Resources\Zapujckas\Tables\ZapujckasTable;
use App\Models\Zapujcka;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ZapujckaResource extends Resource
{
    protected static ?string $model = Zapujcka::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Zápůjčky';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Zápůjčky';

    protected static ?string $modelLabel = 'zápůjčka';

    protected static ?string $pluralModelLabel = 'Zápůjčky';

    protected static ?string $recordTitleAttribute = 'cislo';

    public static function getNavigationBadge(): ?string
    {
        $n = Zapujcka::where('stav', 'zapujceno')->count();

        return $n ? (string) $n : null;
    }

    public static function form(Schema $schema): Schema
    {
        return ZapujckaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ZapujckasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZapujckas::route('/'),
            'create' => CreateZapujcka::route('/create'),
            'edit' => EditZapujcka::route('/{record}/edit'),
        ];
    }
}
