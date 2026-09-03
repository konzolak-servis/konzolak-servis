<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\JenProAdmina;
use App\Http\Controllers\ZalohaController;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use UnitEnum;

class Zalohy extends Page
{
    use JenProAdmina;

    protected string $view = 'filament.pages.zalohy';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Nastavení';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Zálohy';

    protected static ?string $title = 'Zálohy dat';

    /** Data pro view. */
    public function getSeznamProperty(): array
    {
        return ZalohaController::seznam()->map(fn ($f) => [
            'nazev' => $f->getFilename(),
            'velikost' => round($f->getSize() / 1048576, 2),
            'datum' => \Illuminate\Support\Carbon::createFromTimestamp($f->getMTime()),
        ])->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('stahnout')
                ->label('Stáhnout poslední zálohu')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->url(fn () => route('zaloha.stahnout'))
                ->openUrlInNewTab()
                ->visible(fn () => ! empty($this->seznam)),

            Action::make('vytvorit')
                ->label('Vytvořit zálohu nyní')
                ->icon(Heroicon::OutlinedPlus)
                ->color('gray')
                ->action(function () {
                    Artisan::call('zaloha:data');

                    Notification::make()
                        ->title('Záloha vytvořena')
                        ->body(trim(Artisan::output()))
                        ->success()
                        ->send();
                }),
        ];
    }
}
