<?php

namespace App\Filament\Resources\Fakturas;

use App\Filament\Resources\Fakturas\Pages\CreateFaktura;
use App\Filament\Resources\Fakturas\Pages\EditFaktura;
use App\Filament\Resources\Fakturas\Pages\ListFakturas;
use App\Filament\Resources\Fakturas\RelationManagers\PolozkyRelationManager;
use App\Filament\Resources\Fakturas\Schemas\FakturaForm;
use App\Filament\Resources\Fakturas\Tables\FakturasTable;
use App\Models\Faktura;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FakturaResource extends Resource
{
    protected static ?string $model = Faktura::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Faktury';

    protected static ?string $modelLabel = 'faktura';

    protected static ?string $pluralModelLabel = 'Faktury';

    protected static ?string $recordTitleAttribute = 'cislo';

    public static function form(Schema $schema): Schema
    {
        return FakturaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FakturasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFakturas::route('/'),
            'create' => CreateFaktura::route('/create'),
            'edit' => EditFaktura::route('/{record}/edit'),
        ];
    }
}
