<?php

namespace App\Filament\Resources\Zapujckas\Pages;

use App\Filament\Resources\Zapujckas\ZapujckaResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditZapujcka extends EditRecord
{
    protected static string $resource = ZapujckaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('protokol')->label('Předávací protokol (PDF)')->icon('heroicon-o-document-text')
                    ->url(fn () => $this->record->tiskUrl('protokol'))->openUrlInNewTab(),
                Action::make('kauce')->label('Doklad o přijetí kauce (PDF)')->icon('heroicon-o-banknotes')
                    ->visible(fn () => (float) $this->record->kauce > 0)
                    ->url(fn () => $this->record->tiskUrl('kauce'))->openUrlInNewTab(),
                Action::make('vraceni_pdf')->label('Protokol o vrácení (PDF)')->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn () => $this->record->stav === 'vraceno')
                    ->url(fn () => $this->record->tiskUrl('vraceni'))->openUrlInNewTab(),
            ])->label('Tisk')->icon('heroicon-o-printer')->button()->color('gray'),

            Action::make('vratit')
                ->label('Vrátit zápůjčku')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->visible(fn () => $this->record->stav === 'zapujceno')
                ->modalHeading(fn () => 'Vrácení zápůjčky ' . $this->record->cislo)
                ->modalSubmitActionLabel('Potvrdit vrácení')
                ->schema(fn () => array_merge(
                    [
                        DatePicker::make('datum')->label('Vráceno dne')->default(now())->native(false)->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, $set) => $state ? $set('poplatek', $this->record->vypocetPoplatku($state)) : null),
                        TextInput::make('poplatek')->label('Poplatek za zapůjčení')->numeric()->suffix('Kč')->minValue(0)
                            ->default(fn () => $this->record->vypocetPoplatku(now()->toDateString()))
                            ->visible(fn () => ! $this->record->zdarma && (float) $this->record->denni_sazba > 0)
                            ->helperText(fn ($get) => $this->record->pocetDni($get('datum') ?: null) . ' dní × '
                                . number_format((float) $this->record->denni_sazba, 0, ',', ' ') . ' Kč. Strhne se z kauce, zapíše se jako příjem. Můžeš upravit.'),
                    ],
                    $this->record->polozky->map(fn ($p) => TextInput::make('stav_' . $p->id)
                        ->label('Stav: ' . $p->nazev . ($p->seriove_cislo ? ' (SN ' . $p->seriove_cislo . ')' : ''))
                        ->default('v pořádku, kompletní'))->all(),
                    [
                        TextInput::make('srazka')->label('Srazit z kauce')->numeric()->default(0)->suffix('Kč')
                            ->minValue(0)->maxValue((float) $this->record->kauce)
                            ->helperText('Kauce ' . number_format((float) $this->record->kauce, 0, ',', ' ')
                                . ' Kč. Sražená část (poškození, chybějící příslušenství) se zapíše jako příjem.'),
                        TextInput::make('duvod')->label('Důvod srážky'),
                        Textarea::make('poznamka')->label('Poznámka k vrácení')->rows(2),
                    ],
                ))
                ->action(function (array $data) {
                    $stavy = [];
                    foreach ($this->record->polozky as $p) {
                        $stavy[$p->id] = $data['stav_' . $p->id] ?? null;
                    }
                    $this->record->vratit($data['datum'], (float) ($data['srazka'] ?? 0), $data['duvod'] ?? null, $data['poznamka'] ?? null, $stavy,
                        array_key_exists('poplatek', $data) ? (float) $data['poplatek'] : null);

                    $z = $this->record->fresh();
                    Notification::make()->success()->title('Zápůjčka vrácena')
                        ->body(($z->doplatek() > 0
                            ? 'Zákazník doplácí: ' . number_format($z->doplatek(), 0, ',', ' ') . ' Kč.'
                            : 'Vrátit zákazníkovi: ' . number_format($z->vracenaKauce(), 0, ',', ' ') . ' Kč.')
                            . ' Vytiskni protokol o vrácení k podpisu.')
                        ->persistent()->send();
                    $this->redirect(ZapujckaResource::getUrl('edit', ['record' => $this->record]));
                }),

            Action::make('zrusit_vraceni')
                ->label('Zrušit vrácení')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->record->stav === 'vraceno')
                ->requiresConfirmation()
                ->modalDescription('Zápůjčka bude znovu vedena jako zapůjčená a případná srážka i poplatek se odeberou z peněžního deníku.')
                ->action(function () {
                    $this->record->zrusitVraceni();
                    $this->redirect(ZapujckaResource::getUrl('edit', ['record' => $this->record]));
                }),

            DeleteAction::make(),
        ];
    }
}
