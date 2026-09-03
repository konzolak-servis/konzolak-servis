<?php

namespace App\Filament\Resources\Pristups;

use App\Filament\Concerns\JenProAdmina;
use App\Filament\Resources\Pristups\Pages\CreatePristup;
use App\Filament\Resources\Pristups\Pages\EditPristup;
use App\Filament\Resources\Pristups\Pages\ListPristups;
use App\Filament\Resources\Pristups\Schemas\PristupForm;
use App\Filament\Resources\Pristups\Tables\PristupsTable;
use App\Models\Pristup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PristupResource extends Resource
{
    use JenProAdmina;

    protected static ?string $model = Pristup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Nastavení';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Přístupy a hesla';

    protected static ?string $modelLabel = 'přístup';

    protected static ?string $pluralModelLabel = 'Přístupy a hesla';

    protected static ?string $recordTitleAttribute = 'nazev';

    public static function form(Schema $schema): Schema
    {
        return PristupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PristupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPristups::route('/'),
            'create' => CreatePristup::route('/create'),
            'edit' => EditPristup::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Pristup::where('aktivni', true)->whereNotNull('platnost_do')->get()
            ->filter(fn ($p) => $p->jeNaSpadnuti())->count();

        return $n ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
