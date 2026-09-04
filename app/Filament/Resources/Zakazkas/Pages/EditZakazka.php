<?php

namespace App\Filament\Resources\Zakazkas\Pages;

use App\Filament\Resources\Zakazkas\ZakazkaResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditZakazka extends EditRecord
{
    protected static string $resource = ZakazkaResource::class;

    /** Stav zakázky před uložením formuláře – kvůli detekci přechodu na „Hotovo". */
    private ?string $stavPredUlozenim = null;

    protected function beforeSave(): void
    {
        $this->stavPredUlozenim = $this->record->getOriginal('stav');
    }

    protected function afterSave(): void
    {
        // Když se stav ve formuláři přepne na „Hotovo" (ne přes tlačítko
        // „Opraveno + oznámit"), stejně pošli zákazníkovi oznámení o vyzvednutí.
        if ($this->record->wasChanged('stav')
            && $this->record->stav === 'hotovo'
            && $this->stavPredUlozenim !== 'hotovo'
            && $this->stavPredUlozenim !== 'vydano') {
            $this->oznamZakaznikovi();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Uložit i nahoře, ať se nemusí rolovat dolů
            Action::make('ulozit_nahore')
                ->label('Uložit')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->button()
                ->action(fn () => $this->save()),

            // rychlá změna stavu – sbaleno do jednoho tlačítka
            ActionGroup::make([
                Action::make('stav_diagnostika')
                    ->label('Diagnostikováno')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->visible(fn () => ! in_array($this->record->stav, ['diagnostika', 'hotovo', 'vydano'], true))
                    ->action(fn () => $this->nastavStav('diagnostika')),

                Action::make('stav_ceka_na_dil')
                    ->label('Čeká na díl')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->visible(fn () => ! in_array($this->record->stav, ['ceka_na_dil', 'vydano'], true))
                    ->action(fn () => $this->nastavStav('ceka_na_dil')),

                Action::make('stav_hotovo')
                    ->label('Opraveno + oznámit zákazníkovi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn () => ! in_array($this->record->stav, ['hotovo', 'vydano'], true))
                    ->action(function () {
                        $this->nastavStav('hotovo');
                        $this->oznamZakaznikovi();
                    }),

                Action::make('oznamit')
                    ->label('Znovu oznámit vyzvednutí')
                    ->icon('heroicon-o-bell-alert')
                    ->color('info')
                    ->visible(fn () => $this->record->jeHotovo())
                    ->action(fn () => $this->oznamZakaznikovi()),
            ])
                ->label('Změnit stav')
                ->icon('heroicon-o-arrow-path')
                ->button()
                ->color('gray')
                ->visible(fn () => $this->record->stav !== 'vydano'),

            Action::make('znovu_otevrit')
                ->label('Znovu otevřít')
                ->icon('heroicon-o-lock-open')
                ->color('gray')
                ->visible(fn () => $this->record->stav === 'vydano')
                ->requiresConfirmation()
                ->modalDescription('Vrátí zakázku ze stavu „Vydáno" zpět na „Hotovo" pro doúpravy.')
                ->action(function () {
                    $this->record->update(['stav' => 'hotovo']);
                    $this->fillForm();
                    Notification::make()->title('Zakázka znovu otevřena')->success()->send();
                }),

            // uzavřít zakázku → nastaví „vydáno", zeptá se na způsob platby a otevře protokol
            Action::make('uzavrit')
                ->label('Uzavřít zakázku')
                ->icon('heroicon-o-lock-closed')
                ->color('primary')
                ->button()
                ->visible(fn () => $this->record->stav !== 'vydano')
                ->schema([
                    \Filament\Forms\Components\Radio::make('zpusob_uhrady')
                        ->label('Platba')
                        ->options(\App\Models\Zakazka::ZPUSOBY_UHRADY)
                        ->default($this->record->zpusob_uhrady ?? 'hotove')
                        ->inline()
                        ->required(),
                ])
                ->modalHeading('Uzavřít zakázku')
                ->modalDescription('Nastaví „Vydáno", zapíše příjem do peněžního deníku a pošle zákazníkovi servisní protokol.')
                ->modalSubmitActionLabel('Uzavřít zakázku')
                ->action(function (array $data) {
                    $this->record->update([
                        'stav' => 'vydano',
                        'zpusob_uhrady' => $data['zpusob_uhrady'],
                        'datum_vyrizeni' => $this->record->datum_vyrizeni ?? now()->toDateString(),
                    ]);

                    // sesynchronizovat formulář s uloženým stavem, ať ho pozdější uložení
                    // formuláře nepřepíše zpět na starou hodnotu
                    $this->fillForm();

                    $this->odesliProtokolEmailem();

                    Notification::make()
                        ->title('Zakázka ' . $this->record->cislo . ' uzavřena')
                        ->body('Platba ' . (\App\Models\Zakazka::ZPUSOBY_UHRADY[$data['zpusob_uhrady']] ?? '')
                            . ' · zapsáno do peněžního deníku. Protokol najdeš v „Další → Servisní protokol".')
                        ->success()
                        ->send();
                }),

            Action::make('vytvorit_fakturu')
                ->label(fn () => $this->record->faktura
                    ? 'Otevřít fakturu ' . $this->record->faktura->cislo
                    : 'Vytvořit fakturu')
                ->icon(fn () => $this->record->faktura ? 'heroicon-o-document-check' : 'heroicon-o-document-currency-dollar')
                ->color(fn () => $this->record->faktura ? 'success' : 'gray')
                ->action(function () {
                    if ($this->record->faktura) {
                        return redirect(\App\Filament\Resources\Fakturas\FakturaResource::getUrl('edit', ['record' => $this->record->faktura]));
                    }

                    $f = \App\Models\Faktura::create([
                        'zakaznik_id' => $this->record->zakaznik_id,
                        'zakazka_id' => $this->record->id,
                        'forma_uhrady' => $this->record->zpusob_uhrady === 'hotove' ? 'hotově' : 'převodem',
                        'datum_vystaveni' => now()->toDateString(),
                    ]);

                    $uctovane = $this->record->polozky()->where('uctovat', true)->get();
                    $zarizeni = $this->record->zarizeni?->oznaceni ?? '';

                    if ($uctovane->isEmpty()) {
                        $f->polozky()->create([
                            'zarizeni_text' => $zarizeni,
                            'popis' => 'Oprava ' . $this->record->cislo,
                            'mnozstvi' => 1,
                            'cena' => (float) $this->record->cena_celkem,
                        ]);
                    } else {
                        foreach ($uctovane as $i => $p) {
                            $f->polozky()->create([
                                'zarizeni_text' => $i === 0 ? $zarizeni : '',
                                'popis' => $p->nazev,
                                'mnozstvi' => $p->mnozstvi,
                                'cena' => $p->cena_ks,
                            ]);
                        }
                    }

                    if ($this->record->zaloha > 0) {
                        $f->polozky()->create([
                            'popis' => 'Uhrazená záloha',
                            'mnozstvi' => 1,
                            'cena' => -1 * (float) $this->record->zaloha,
                        ]);
                    }

                    $f->refresh();
                    $this->odesliFakturuEmailem($f);

                    return redirect(\App\Filament\Resources\Fakturas\FakturaResource::getUrl('edit', ['record' => $f]));
                }),

            // WhatsApp – rychlá zpráva zákazníkovi ze šablon
            ActionGroup::make($this->whatsappAkce())
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->button()
                ->visible(fn () => $this->telefonMezinarodne() !== null),

            ActionGroup::make([
                Action::make('servisni_doklad')
                    ->label('Doklad o převzetí (PDF)')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => route('tisk.zakazka.doklad', $this->record))
                    ->openUrlInNewTab(),
                Action::make('servisni_protokol')
                    ->label('Servisní protokol (PDF)')
                    ->icon('heroicon-o-document-check')
                    ->url(fn () => route('tisk.zakazka.protokol', $this->record))
                    ->openUrlInNewTab(),
                Action::make('stitek')
                    ->label('Štítek na zařízení (PDF)')
                    ->icon('heroicon-o-tag')
                    ->url(fn () => route('tisk.zakazka.stitek', $this->record))
                    ->openUrlInNewTab(),

                Action::make('mail_doklad')
                    ->label('Poslat doklad e-mailem')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->visible(fn () => filled($this->record->zakaznik?->email))
                    ->requiresConfirmation()
                    ->modalDescription(fn () => 'Doklad o převzetí se odešle na ' . $this->record->zakaznik?->email)
                    ->action(fn () => $this->odesliDokladEmailem()),
                Action::make('mail_protokol')
                    ->label('Poslat protokol e-mailem')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->visible(fn () => filled($this->record->zakaznik?->email))
                    ->requiresConfirmation()
                    ->modalDescription(fn () => 'Servisní protokol se odešle na ' . $this->record->zakaznik?->email)
                    ->action(fn () => $this->odesliProtokolEmailem()),
                Action::make('mail_faktura')
                    ->label(fn () => 'Poslat fakturu ' . $this->record->faktura?->cislo . ' e-mailem')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->visible(fn () => $this->record->faktura && filled($this->record->zakaznik?->email))
                    ->requiresConfirmation()
                    ->modalDescription(fn () => 'Faktura se odešle na ' . $this->record->zakaznik?->email)
                    ->action(fn () => $this->odesliFakturuEmailem($this->record->faktura)),

                Action::make('reklamace')
                    ->label('Založit reklamaci')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn () => $this->record->jeHotovo() || $this->record->stav === 'vydano')
                    ->requiresConfirmation()
                    ->modalDescription('Vytvoří novou zakázku navázanou na tuto jako reklamaci (bez ceny).')
                    ->action(function () {
                        $r = \App\Models\Zakazka::create([
                            'reklamace_k_id' => $this->record->id,
                            'zakaznik_id' => $this->record->zakaznik_id,
                            'zarizeni_id' => $this->record->zarizeni_id,
                            'stav' => 'prijato',
                            'popis_zavady' => 'Reklamace zakázky ' . $this->record->cislo
                                . "\nPůvodní práce: " . ($this->record->navrh_reseni_prace ?: '—'),
                            'zaruka_mesice' => $this->record->zaruka_mesice,
                        ]);

                        return redirect(\App\Filament\Resources\Zakazkas\ZakazkaResource::getUrl('edit', ['record' => $r]));
                    }),

                DeleteAction::make(),
            ])
                ->label('Další')
                ->icon('heroicon-o-ellipsis-horizontal')
                ->button()
                ->color('gray'),
        ];
    }

    private function nastavStav(string $stav): void
    {
        $this->record->update(['stav' => $stav]);
        $this->fillForm();

        Notification::make()
            ->title('Stav změněn na „' . (\App\Models\Zakazka::STAVY[$stav] ?? $stav) . '"')
            ->success()
            ->send();
    }

    /** Pošle zákazníkovi doklad o převzetí zařízení do opravy v PDF. */
    private function odesliDokladEmailem(): void
    {
        $z = $this->record->fresh();
        $email = $z->zakaznik?->email;

        if (! $email) {
            Notification::make()->title('Zákazník nemá e-mail – doklad pošli ručně')->warning()->send();

            return;
        }

        try {
            $pdf = (new \App\Http\Controllers\TiskController)->servisniDoklad($z)->getContent();
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\DokladZakazky($z, $pdf));

            Notification::make()->title('Doklad o převzetí odeslán na ' . $email)->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Doklad se nepodařilo odeslat e-mailem')
                ->body('Pošli ho ručně. ' . $e->getMessage())->danger()->send();
        }
    }

    /** Pošle vytvořenou fakturu zákazníkovi e-mailem (PDF příloha). */
    private function odesliFakturuEmailem(\App\Models\Faktura $f): void
    {
        $email = $f->zakaznik?->email;

        if (! $email) {
            Notification::make()->title('Faktura ' . $f->cislo . ' vytvořena')
                ->body('Zákazník nemá e-mail – fakturu pošli ručně.')->success()->send();

            return;
        }

        try {
            $pdf = (new \App\Http\Controllers\TiskController)->faktura($f)->getContent();
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\FakturaZakaznikovi($f, $pdf));

            Notification::make()->title('Faktura ' . $f->cislo . ' vytvořena a odeslána na ' . $email)
                ->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Faktura ' . $f->cislo . ' vytvořena, ale e-mail selhal')
                ->body('Pošli ji ručně. ' . $e->getMessage())->warning()->send();
        }
    }

    /** Po uzavření zakázky pošle zákazníkovi servisní protokol v PDF (fakturu ne – ta jde zvlášť). */
    private function odesliProtokolEmailem(): void
    {
        $z = $this->record->fresh();
        $zk = $z->zakaznik;

        if (! $zk?->email) {
            Notification::make()->title('Zákazník nemá e-mail – protokol pošli ručně')->warning()->send();

            return;
        }

        try {
            $prilohy = [
                'servisni-protokol-' . $z->cislo . '.pdf'
                    => (new \App\Http\Controllers\TiskController)->servisniProtokol($z)->getContent(),
            ];

            \Illuminate\Support\Facades\Mail::to($zk->email)->send(new \App\Mail\ProtokolZakazky($z, $prilohy));

            Notification::make()->title('Servisní protokol odeslán na ' . $zk->email)->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Protokol se nepodařilo odeslat e-mailem')
                ->body('Zakázka je uzavřená, protokol pošli ručně. ' . $e->getMessage())
                ->danger()->send();
        }
    }

    /** Krátká zpráva pro zákazníka (SMS / WhatsApp) – „zakázka je hotová". */
    private function zpravaProZakaznika(): string
    {
        $z = $this->record;

        return 'Konzolák Zlín: Vaše zakázka ' . $z->cislo
            . ' je hotová a připravená k vyzvednutí. '
            . ($z->cena_celkem > 0 ? 'K úhradě ' . number_format($z->cena_celkem - $z->zaloha, 0, ' ', ' ') . ' Kč. ' : '')
            . 'Tel. ' . (\App\Models\Firma::get()->telefon ?: '');
    }

    /** Doplní zástupné značky {cislo}, {cena}, {odkaz}… do textu šablony. */
    private function vyplnSablonu(string $text): string
    {
        $z = $this->record;
        $firma = \App\Models\Firma::get();
        $doplatek = max((float) $z->cena_celkem - (float) $z->zaloha, 0);

        $odkaz = route('verejne.stav', [
            'zakazka' => $z->id,
            'token' => \App\Support\QrPlatba::token('stav', $z->id),
        ]);

        return strtr($text, [
            '{cislo}' => $z->cislo,
            '{zarizeni}' => $z->zarizeni?->oznaceni ?? '',
            '{zakaznik}' => $z->zakaznik?->nazev ?? '',
            '{cena}' => number_format((float) $z->cena_celkem, 0, ',', ' '),
            '{doplatek}' => number_format($doplatek, 0, ',', ' '),
            '{zaloha}' => number_format((float) $z->zaloha, 0, ',', ' '),
            '{stav}' => \App\Models\Zakazka::STAVY[$z->stav] ?? $z->stav,
            '{tel}' => $firma->telefon ?? '',
            '{firma}' => $firma->nazev ?? '',
            '{odkaz}' => $odkaz,
        ]);
    }

    /** WhatsApp akce ze šablon (typ 'whatsapp') + rychlá „hotovo". */
    private function whatsappAkce(): array
    {
        $tel = $this->telefonMezinarodne();

        $wa = fn (string $text) => 'https://wa.me/' . $tel . '?text=' . rawurlencode($text);

        $akce = [
            Action::make('wa_hotovo')
                ->label('Rychlé: hotovo k vyzvednutí')
                ->icon('heroicon-o-check-circle')
                ->url(fn () => $wa($this->zpravaProZakaznika()))
                ->openUrlInNewTab(),
        ];

        foreach (\App\Models\Sablona::query()
            ->where('typ', 'whatsapp')->where('aktivni', true)
            ->orderBy('poradi')->orderBy('nazev')->get() as $s) {
            $akce[] = Action::make('wa_' . $s->id)
                ->label($s->nazev)
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(fn () => $wa($this->vyplnSablonu($s->text)))
                ->openUrlInNewTab();
        }

        return $akce;
    }

    /** Telefon zákazníka v mezinárodním tvaru bez znaků (např. 420773001488) – pro wa.me. */
    private function telefonMezinarodne(): ?string
    {
        $tel = preg_replace('/\D+/', '', (string) $this->record->zakaznik?->telefon);

        if (! $tel) {
            return null;
        }

        if (strlen($tel) === 9) {
            $tel = '420' . $tel;              // české číslo bez předvolby
        }

        return $tel;
    }

    /** Pošle zákazníkovi e-mail o vyzvednutí a nabídne odeslání zprávy přes WhatsApp. */
    private function oznamZakaznikovi(): void
    {
        $z = $this->record;
        $zk = $z->zakaznik;

        $zprava = $this->zpravaProZakaznika();

        // E-mail
        if ($zk?->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($zk->email)->send(new \App\Mail\ZakazkaHotova($z));
                Notification::make()->title('E-mail odeslán na ' . $zk->email)->success()->send();
            } catch (\Throwable $e) {
                Notification::make()->title('E-mail se nepodařilo odeslat')
                    ->body('Zkontroluj nastavení pošty (MAIL_* v .env). ' . $e->getMessage())
                    ->danger()->send();
            }
        } else {
            Notification::make()->title('Zákazník nemá e-mail')->warning()->send();
        }

        // Nabídni odeslání přes WhatsApp – otevře wa.me s předvyplněnou zprávou
        $wa = $this->telefonMezinarodne();

        if ($wa) {
            Notification::make()
                ->title('Poslat zákazníkovi přes WhatsApp')
                ->body('Otevře WhatsApp s připravenou zprávou' . ($zk?->telefon ? ' (' . $zk->telefon . ')' : '') . '.')
                ->info()
                ->duration(30000)
                ->actions([
                    Action::make('whatsapp')
                        ->label('Otevřít WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->url('https://wa.me/' . $wa . '?text=' . rawurlencode($zprava))
                        ->openUrlInNewTab(),
                ])
                ->send();
        }
    }
}
