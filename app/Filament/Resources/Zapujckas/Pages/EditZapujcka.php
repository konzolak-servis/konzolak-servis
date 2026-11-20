<?php

namespace App\Filament\Resources\Zapujckas\Pages;

use App\Filament\Resources\Zapujckas\ZapujckaResource;
use Filament\Actions\Action;
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
            Action::make('protokol')->label('Předávací protokol')->icon('heroicon-o-document-text')->color('primary')
                ->url(fn () => $this->record->tiskUrl('protokol'))->openUrlInNewTab(),
            Action::make('kauce')->label('Doklad kauce')->icon('heroicon-o-banknotes')->color('gray')
                ->visible(fn () => (float) $this->record->kauce > 0)
                ->url(fn () => $this->record->tiskUrl('kauce'))->openUrlInNewTab(),
            Action::make('vraceni_pdf')->label('Protokol o vrácení')->icon('heroicon-o-document-check')->color('primary')
                ->visible(fn () => $this->record->stav === 'vraceno')
                ->url(fn () => $this->record->tiskUrl('vraceni'))->openUrlInNewTab(),
            Action::make('vyuctovani_pdf')->label('Vyúčtování kauce')->icon('heroicon-o-banknotes')->color('primary')
                ->visible(fn () => $this->record->stav === 'vraceno')
                ->url(fn () => $this->record->tiskUrl('vyuctovani'))->openUrlInNewTab(),

            // Nic se neposílá automaticky – jen ručně tímto tlačítkem (stejně jako u zakázek).
            Action::make('poslat_email')
                ->label('Poslat e-mailem')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->modalHeading(fn () => 'Poslat zákazníkovi ' . ($this->record->stav === 'vraceno' ? 'protokol o vrácení' : 'předávací protokol'))
                ->modalSubmitActionLabel('Odeslat')
                ->schema(fn () => [
                    TextInput::make('email')->label('Odeslat na e-mail')->email()->required()
                        ->default($this->record->zakaznik?->email)
                        ->helperText($this->record->zakaznik?->email ? null : 'Zákazník nemá uložený e-mail – zadej adresu ručně.'),
                ])
                ->action(function (array $data) {
                    $z = $this->record->fresh(['zakaznik', 'zakazka', 'polozky']);
                    $tisk = new \App\Http\Controllers\TiskController;
                    $prilohy = $z->stav === 'vraceno'
                        ? [
                            'vraceni-zarizeni-' . $z->cislo . '.pdf' => $tisk->zapujckaVraceni($z)->getContent(),
                            'vyuctovani-kauce-' . $z->cislo . '.pdf' => $tisk->zapujckaVyuctovani($z)->getContent(),
                        ]
                        : array_filter([
                            'predavaci-protokol-' . $z->cislo . '.pdf' => $tisk->zapujckaProtokol($z)->getContent(),
                            'prijem-kauce-' . $z->cislo . '.pdf' => (float) $z->kauce > 0 ? $tisk->zapujckaKauce($z)->getContent() : null,
                        ]);

                    try {
                        \Illuminate\Support\Facades\Mail::to(trim($data['email']))->send(new \App\Mail\ZapujckaDokumenty($z, $prilohy));
                        Notification::make()->success()->title('Odesláno na ' . trim($data['email']))->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('E-mail se nepodařilo odeslat')->body($e->getMessage())->send();
                    }
                }),

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
                        TextInput::make('poplatek')->label('Nájemné (vč. případného prodlení)')->numeric()->suffix('Kč')->minValue(0)
                            ->default(fn () => $this->record->vypocetPoplatku(now()->toDateString()))
                            ->visible(fn () => ! $this->record->zdarma && ((float) $this->record->denni_sazba > 0 || $this->record->maVikendovyTarif()))
                            ->helperText(fn ($get) => $this->record->pocetDni($get('datum') ?: null) . ' ' . \App\Models\Zapujcka::dnu($this->record->pocetDni($get('datum') ?: null)) . ': '
                                . collect($this->record->rozpisPoplatku($get('datum') ?: null))
                                    ->map(fn ($r) => $r['popis'] . ' à ' . number_format($r['sazba'], 0, ',', ' ') . ' Kč')->implode(', ')
                                . (($pr = $this->record->dnyProdleni($get('datum') ?: null)) > 0 ? ' – z toho prodlení ' . $pr . ' ' . \App\Models\Zapujcka::dnu($pr) . ' po termínu' : '')
                                . '. Strhne se z kauce, zapíše se jako příjem. Můžeš upravit.'),
                    ],
                    $this->record->polozky->map(fn ($p) => TextInput::make('stav_' . $p->id)
                        ->label('Stav: ' . $p->nazev . ($p->seriove_cislo ? ' (SN ' . $p->seriove_cislo . ')' : ''))
                        ->default('v pořádku, kompletní'))->all(),
                    [
                        \Filament\Schemas\Components\Fieldset::make('Srážky z kauce (náhrada škody)')
                            ->columns(3)
                            ->schema(collect(\App\Models\Zapujcka::DRUHY_SRAZEK)->flatMap(fn ($nazev, $druh) => [
                                TextInput::make('srazka_' . $druh)->label($nazev)->numeric()->minValue(0)->suffix('Kč')->placeholder('0'),
                                TextInput::make('popis_' . $druh)->label('Popis')->columnSpan(2)
                                    ->placeholder(match ($druh) {
                                        'poskozeni' => 'např. prasklý kryt, nefunkční HDMI port',
                                        'prislusenstvi' => 'např. chybí HDMI kabel',
                                        'cisteni' => 'např. silně znečištěno, kouř',
                                        default => 'důvod',
                                    }),
                            ])->values()->all()),
                        \Filament\Forms\Components\Radio::make('forma')->label('Vyrovnání (vrácení kauce / doplatek)')
                            ->options(['hotově' => 'Hotově', 'převodem' => 'Převodem'])
                            ->default(fn () => $this->record->kauce_forma ?: 'hotově')->inline(),
                        Textarea::make('poznamka')->label('Poznámka k vrácení')->rows(2),
                    ],
                ))
                ->action(function (array $data) {
                    $stavy = [];
                    foreach ($this->record->polozky as $p) {
                        $stavy[$p->id] = $data['stav_' . $p->id] ?? null;
                    }
                    $srazky = [];
                    foreach (array_keys(\App\Models\Zapujcka::DRUHY_SRAZEK) as $druh) {
                        if ((float) ($data['srazka_' . $druh] ?? 0) > 0) {
                            $srazky[] = ['druh' => $druh, 'popis' => $data['popis_' . $druh] ?? null, 'castka' => (float) $data['srazka_' . $druh]];
                        }
                    }
                    $this->record->vratit($data['datum'], 0, null, $data['poznamka'] ?? null, $stavy,
                        array_key_exists('poplatek', $data) ? (float) $data['poplatek'] : null, $srazky, $data['forma'] ?? null);

                    $z = $this->record->fresh();
                    Notification::make()->success()->title('Zápůjčka vrácena')
                        ->body(($z->doplatek() > 0
                            ? 'Zákazník doplácí: ' . number_format($z->doplatek(), 0, ',', ' ') . ' Kč.'
                            : 'Vrátit zákazníkovi: ' . number_format($z->vracenaKauce(), 0, ',', ' ') . ' Kč.')
                            . ' Vytiskni protokol o vrácení a vyúčtování kauce k podpisu (2×).')
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
