<?php

namespace App\Filament\Resources\Nabidkas;

use App\Filament\Resources\Nabidkas\Pages\CreateNabidka;
use App\Filament\Resources\Nabidkas\Pages\EditNabidka;
use App\Filament\Resources\Nabidkas\Pages\ListNabidkas;
use App\Filament\Resources\Nabidkas\RelationManagers\PolozkyRelationManager;
use App\Filament\Resources\Nabidkas\Schemas\NabidkaForm;
use App\Filament\Resources\Nabidkas\Tables\NabidkasTable;
use App\Models\Nabidka;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NabidkaResource extends Resource
{
    protected static ?string $model = Nabidka::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Nabídky / PC sestavy';

    protected static ?string $modelLabel = 'nabídka';

    protected static ?string $pluralModelLabel = 'Nabídky';

    protected static ?string $recordTitleAttribute = 'cislo';

    public static function form(Schema $schema): Schema
    {
        return NabidkaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NabidkasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNabidkas::route('/'),
            'create' => CreateNabidka::route('/create'),
            'edit' => EditNabidka::route('/{record}/edit'),
        ];
    }
}
