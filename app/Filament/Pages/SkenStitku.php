<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Naskenuje QR ze štítku/dokladu telefonem a přesměruje rovnou na zakázku v systému. */
class SkenStitku extends Page
{
    protected string $view = 'filament.pages.sken-stitku';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Servis';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Sken štítku';

    protected static ?string $title = 'Sken štítku';

    protected static ?string $slug = 'sken-stitku';
}
