<?php

namespace App\Support;

use App\Http\Controllers\TiskController;
use App\Mail\FakturaZakaznikovi;
use App\Models\Faktura;
use App\Models\Firma;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

/** Odeslání faktury zákazníkovi e-mailem – volitelně i se servisním protokolem zakázky. */
class FakturaMailer
{
    public static function odesli(Faktura $f, string $email, bool $sProtokolem = false): void
    {
        // Datum vystavení = den odeslání, splatnost od něj (Nastavení firmy → Splatnost faktur).
        // U uhrazené faktury se data nemění (posílá se jen kopie).
        if (! $f->uhrazeno) {
            $f->update([
                'datum_vystaveni' => now()->toDateString(),
                'datum_splatnosti' => now()->addDays((int) (Firma::get()->splatnost_dni ?: 14))->toDateString(),
            ]);
        }

        $tisk = new TiskController;
        $pdf = $tisk->faktura($f)->getContent();

        $dalsi = [];
        if ($sProtokolem && $f->zakazka) {
            $dalsi['servisni-protokol-' . $f->zakazka->cislo . '.pdf'] = $tisk->servisniProtokol($f->zakazka)->getContent();
        }

        Mail::to($email)->send(new FakturaZakaznikovi($f, $pdf, $dalsi));
    }

    /** Tlačítko „Odeslat e-mailem" pro detail i seznam faktur. */
    public static function akce(): Action
    {
        return Action::make('odeslat_email')
            ->label('Odeslat e-mailem')
            ->icon('heroicon-o-paper-airplane')
            ->modalHeading(fn (Faktura $record) => 'Odeslat fakturu ' . $record->cislo)
            ->modalSubmitActionLabel('Odeslat')
            ->schema(fn (Faktura $record) => [
                TextInput::make('email')->label('E-mail')->email()->required()
                    ->default($record->zakaznik?->email),
                Toggle::make('protokol')
                    ->label('Přiložit servisní protokol k zakázce ' . ($record->zakazka?->cislo ?? ''))
                    ->default(true)
                    ->visible((bool) $record->zakazka),
            ])
            ->action(function (Faktura $record, array $data, $livewire) {
                $sProtokolem = ! empty($data['protokol']) && $record->zakazka;

                try {
                    self::odesli($record, trim($data['email']), (bool) $sProtokolem);

                    // v detailu faktury promítnout nová data do formuláře (ať je „Uložit" nepřepíše)
                    if (method_exists($livewire, 'refreshFormData')) {
                        $livewire->refreshFormData(['datum_vystaveni', 'datum_splatnosti']);
                    }

                    Notification::make()
                        ->title(($sProtokolem ? 'Faktura a protokol odeslány' : 'Faktura odeslána') . ' na ' . trim($data['email']))
                        ->success()->send();
                } catch (\Throwable $e) {
                    Notification::make()->title('E-mail se nepodařilo odeslat')
                        ->body($e->getMessage())->danger()->send();
                }
            });
    }
}
