<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\JenProAdmina;
use App\Http\Controllers\ZalohaController;
use App\Support\ZalohaUsb;
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

    /** Cílová složka na USB disku, nebo null, když disk není připojený. */
    public function getUsbSlozkaProperty(): ?string
    {
        $disk = ZalohaUsb::disk();

        return $disk === null ? null : $disk . config('services.zaloha.usb_slozka');
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

            Action::make('usb')
                ->label('Zálohovat na USB')
                ->icon(Heroicon::OutlinedArrowUpOnSquareStack)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Záloha na USB disk')
                ->modalDescription('Vytvoří novou zálohu a na disk „' . config('services.zaloha.usb_label')
                    . '" zkopíruje všechny zálohy, které tam ještě nejsou. Disk musí být připojený.')
                ->modalSubmitActionLabel('Zálohovat')
                ->action(function () {
                    if (ZalohaUsb::disk() === null) {
                        Notification::make()
                            ->title('USB disk není připojený')
                            ->body('Připoj disk „' . config('services.zaloha.usb_label') . '" a zkus to znovu.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $kod = Artisan::call('zaloha:data');
                    $vystup = trim(Artisan::output());
                    $ok = $kod === 0 && str_contains($vystup, 'Na USB');

                    $notifikace = Notification::make()
                        ->title($ok ? 'Záloha uložena i na USB' : 'Záloha na USB se nepovedla')
                        ->body($vystup)
                        ->persistent();
                    ($ok ? $notifikace->success() : $notifikace->danger())->send();
                }),

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
