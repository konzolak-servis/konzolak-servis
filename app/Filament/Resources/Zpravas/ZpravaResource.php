<?php

namespace App\Filament\Resources\Zpravas;

use App\Filament\Resources\Zpravas\Pages\ListZpravas;
use App\Filament\Resources\Zpravas\Pages\ViewZprava;
use App\Filament\Resources\Zpravas\Tables\ZpravasTable;
use App\Models\Zprava;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ZpravaResource extends Resource
{
    protected static ?string $model = Zprava::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Pošta';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pošta';

    protected static ?string $modelLabel = 'zpráva';

    protected static ?string $pluralModelLabel = 'Pošta';

    protected static ?string $recordTitleAttribute = 'predmet';

    public static function table(Table $table): Table
    {
        return ZpravasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZpravas::route('/'),
            'view' => ViewZprava::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return Zprava::neprectene()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
