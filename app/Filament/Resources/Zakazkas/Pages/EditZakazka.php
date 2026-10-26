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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return \App\Filament\Resources\Zakazkas\Schemas\ZakazkaForm::naplnDalsi($data, $this->record);
    }

    /**
     * Cena zakázky se při uložení vždy znovu spočítá z účtovaných položek. Otevřený formulář může
     * držet starou hodnotu (položky se přidávají na záložce pod ním) a ta by jinak přepsala součet.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->polozky()->exists()) {
            $data['cena_celkem'] = (float) $this->record->polozky()->where('uctovat', true)->sum('cena_celkem');
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->refreshFormData(['cena_celkem']);

        \App\Filament\Resources\Zakazkas\Schemas\ZakazkaForm::ulozDalsi($this->record, $this->form->getRawState());

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

            // Servisní list (doklad o převzetí) zákazníkovi – jen ručně, nic se neposílá automaticky
            Action::make('mail_doklad')
                ->label('Odeslat servisní list')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->button()
                ->requiresConfirmation(fn () => $this->maEmailZakaznika())
                ->modalHeading('Odeslat servisní list zákazníkovi')
                ->modalDescription(fn () => $this->maEmailZakaznika()
                    ? 'Pošle doklad o převzetí v PDF na ' . $this->record->zakaznik?->email . '.'
                    : null)
                ->modalSubmitActionLabel('Odeslat')
                ->schema(fn () => $this->maEmailZakaznika() ? [] : [$this->polePriEmailu()])
                ->action(fn (array $data) => $this->odesliDokladEmailem($data['email'] ?? null)),

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

            // uzavřít zakázku → nastaví „vydáno", zeptá se na způsob platby a zapíše příjem
            // (protokol e-mailem se posílá už dřív, při přechodu na „hotovo")
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
                ->modalDescription('Nastaví „Vydáno" a zapíše příjem do peněžního deníku. Servisní protokol už zákazník '
                    . 'dostal e-mailem při přechodu na „Hotovo" – tady se znovu neposílá.')
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
                ->modalHeading('Vytvořit fakturu')
                ->modalDescription('Variabilní symbol se vyplní číslem zakázky. Fakturuješ-li na firmu, zadej IČO – odběratel se najde nebo načte z registru ARES.')
                ->modalSubmitActionLabel('Vytvořit fakturu')
                ->schema(fn () => $this->record->faktura ? [] : [
                    \Filament\Forms\Components\TextInput::make('ico')->label('IČO odběratele (nepovinné)')
                        ->placeholder('nech prázdné = zákazník zakázky')->maxLength(12),
                ])
                ->action(function (array $data, \Filament\Actions\Action $action) {
                    if ($this->record->faktura) {
                        return redirect(\App\Filament\Resources\Fakturas\FakturaResource::getUrl('edit', ['record' => $this->record->faktura]));
                    }

                    $zakaznikId = $this->record->zakaznik_id;
                    if (filled($data['ico'] ?? null)) {
                        try {
                            [$firma] = \App\Models\Zakaznik::najdiNeboZalozPodleIco((string) $data['ico']);
                            $zakaznikId = $firma->id;
                            // jméno osoby ze zakázky se uloží u firmy jako kontaktní osoba (zobrazí se na faktuře)
                            if (blank($firma->jmeno) && $this->record->zakaznik && $this->record->zakaznik->id !== $firma->id) {
                                $firma->update(['jmeno' => $this->record->zakaznik->nazev]);
                            }
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->warning()->send();
                            $action->halt();

                            return;
                        }
                    }

                    $f = \App\Models\Faktura::create([
                        'zakaznik_id' => $zakaznikId,
                        'zakazka_id' => $this->record->id,
                        'forma_uhrady' => $this->record->zpusob_uhrady === 'hotove' ? 'hotově' : 'převodem',
                        'datum_vystaveni' => now()->toDateString(),
                    ]);

                    $uctovane = $this->record->polozky()->where('uctovat', true)->get();
                    $zarizeni = $this->record->zarizeniText();

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

                    if ($this->record->slevaCastka() > 0) {
                        $f->polozky()->create([
                            'popis' => $this->record->slevaPopis()
                                . ($this->record->sleva_kod ? ' (' . $this->record->sleva_kod . ')' : ''),
                            'mnozstvi' => 1,
                            'cena' => -1 * $this->record->slevaCastka(),
                        ]);
                    }

                    if ($this->record->cena_dopravy > 0) {
                        $f->polozky()->create([
                            'popis' => $this->record->jeDobirka() ? 'Doprava – dobírka' : 'Doprava',
                            'mnozstvi' => 1,
                            'cena' => (float) $this->record->cena_dopravy,
                        ]);
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
                    ->url(fn () => $this->record->tiskUrl('doklad'))
                    ->openUrlInNewTab(),
                Action::make('servisni_doklad_cb')
                    ->label('Doklad o převzetí – tisk ČB (PDF)')
                    ->icon('heroicon-o-printer')
                    ->url(fn () => $this->record->tiskUrl('doklad', ['cb' => 1]))
                    ->openUrlInNewTab(),
                Action::make('servisni_protokol')
                    ->label('Servisní protokol (PDF)')
                    ->icon('heroicon-o-document-check')
                    ->url(fn () => $this->record->tiskUrl('protokol'))
                    ->openUrlInNewTab(),
                Action::make('servisni_protokol_cb')
                    ->label('Servisní protokol – tisk ČB (PDF)')
                    ->icon('heroicon-o-printer')
                    ->url(fn () => $this->record->tiskUrl('protokol', ['cb' => 1]))
                    ->openUrlInNewTab(),
                Action::make('stitek')
                    ->label('Štítek na zařízení (PDF)')
                    ->icon('heroicon-o-tag')
                    ->url(fn () => $this->record->tiskUrl('stitek'))
                    ->openUrlInNewTab(),

                Action::make('mail_protokol')
                    ->label('Poslat protokol e-mailem')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->schema(fn () => $this->maEmailZakaznika() ? [] : [$this->polePriEmailu()])
                    ->action(fn (array $data) => $this->odesliProtokolEmailem($data['email'] ?? null)),
                Action::make('mail_faktura')
                    ->label(fn () => 'Poslat fakturu ' . $this->record->faktura?->cislo . ' e-mailem')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->visible(fn () => (bool) $this->record->faktura)
                    ->schema(fn () => $this->maEmailZakaznika() ? [] : [$this->polePriEmailu()])
                    ->action(fn (array $data) => $this->odesliFakturuEmailem($this->record->faktura, $data['email'] ?? null)),

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

                        $r->dalsiZarizeni()->attach($this->record->dalsiZarizeni->pluck('id')->all());

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
    /** Má zákazník uložený e-mail? Pak se posílá rovnou bez potvrzení. */
    private function maEmailZakaznika(): bool
    {
        return filled($this->record->zakaznik?->email);
    }

    /** Pole s e-mailem do modalu odesílacích akcí (předvyplněné e-mailem zákazníka). */
    private function polePriEmailu(): \Filament\Forms\Components\TextInput
    {
        return \Filament\Forms\Components\TextInput::make('email')
            ->label('Odeslat na e-mail')
            ->email()
            ->required()
            ->default(fn () => $this->record->zakaznik?->email)
            ->helperText($this->record->zakaznik?->email
                ? null
                : 'Zákazník nemá uložený e-mail – zadej adresu ručně.');
    }

    private function odesliDokladEmailem(?string $email = null): void
    {
        $z = $this->record->fresh();

        try {
            $poslanoNa = \App\Support\ZakazkaMailer::posliDoklad($z, $email);

            if (! $poslanoNa) {
                Notification::make()->title('Zadej e-mail, na který se má doklad poslat')->warning()->send();

                return;
            }

            Notification::make()->title('Doklad o převzetí odeslán na ' . $poslanoNa)->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Doklad se nepodařilo odeslat e-mailem')
                ->body('Pošli ho ručně. ' . $e->getMessage())->danger()->send();
        }
    }

    /** Pošle vytvořenou fakturu zákazníkovi e-mailem (PDF příloha). */
    private function odesliFakturuEmailem(\App\Models\Faktura $f, ?string $email = null): void
    {
        $email = $email ?: $f->zakaznik?->email;

        if (! $email) {
            Notification::make()->title('Faktura ' . $f->cislo . ' vytvořena')
                ->body('Zadej e-mail, na který se má faktura poslat.')->success()->send();

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
    private function odesliProtokolEmailem(?string $email = null): void
    {
        $z = $this->record->fresh();
        $email = $email ?: $z->zakaznik?->email;

        if (! $email) {
            Notification::make()->title('Zadej e-mail, na který se má protokol poslat')->warning()->send();

            return;
        }

        try {
            $prilohy = [
                'servisni-protokol-' . $z->cislo . '.pdf'
                    => (new \App\Http\Controllers\TiskController)->servisniProtokol($z)->getContent(),
            ];

            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\ProtokolZakazky($z, $prilohy));

            Notification::make()->title('Servisní protokol odeslán na ' . $email)->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Protokol se nepodařilo odeslat e-mailem')
                ->body('Zakázka je uzavřená, protokol pošli ručně. ' . $e->getMessage())
                ->danger()->send();
        }
    }

    /** Krátká zpráva pro zákazníka (SMS / WhatsApp) – „zakázka je hotová". */
    /** WhatsApp zpráva při převzetí zařízení – doklad o převzetí se přikládá ručně (stažené PDF). */
    private function zpravaPrevzeti(): string
    {
        $z = $this->record;
        $odkaz = route('verejne.stav', [
            'zakazka' => $z->id,
            'token' => \App\Support\QrPlatba::token('stav', $z->id),
        ]);

        return 'Konzolák Zlín: Převzali jsme vaše zařízení' . ($z->zarizeniText() ? ' (' . $z->zarizeniText() . ')' : '')
            . ' do opravy, číslo zakázky ' . $z->cislo . '. Doklad o převzetí posílám v příloze – při vyzvednutí ho prosím předložte. '
            . 'Stav opravy můžete sledovat zde: ' . $odkaz . ' '
            . 'Tel. ' . (\App\Models\Firma::get()->telefon ?: '');
    }

    private function zpravaProZakaznika(): string
    {
        $z = $this->record;

        return 'Konzolák Zlín: Vaše zakázka ' . $z->cislo
            . ($z->zpusob_vydani === 'odeslani' ? ' je hotová, brzy ji odešleme zpět. ' : ' je hotová a připravená k vyzvednutí. ')
            . ($z->cena_celkem > 0 ? ($z->jeDobirka() ? 'Zásilka jde na dobírku (' . number_format($z->doplatek(), 0, ' ', ' ') . ' Kč), předem nic neplaťte. ' : 'K úhradě ' . number_format($z->doplatek(), 0, ' ', ' ') . ' Kč. ') : '')
            . 'Tel. ' . (\App\Models\Firma::get()->telefon ?: '');
    }

    /** Doplní zástupné značky {cislo}, {cena}, {odkaz}… do textu šablony. */
    private function vyplnSablonu(string $text): string
    {
        $z = $this->record;
        $firma = \App\Models\Firma::get();
        $doplatek = $z->doplatek();

        $odkaz = route('verejne.stav', [
            'zakazka' => $z->id,
            'token' => \App\Support\QrPlatba::token('stav', $z->id),
        ]);

        return strtr($text, [
            '{cislo}' => $z->cislo,
            '{zarizeni}' => $z->zarizeniText(),
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

        // wa.me neumí předvyplnit přílohu (omezení WhatsAppu, ne naší appky). Dřív jsem
        // to zkoušel udělat jedním klikem – $this->js() spustilo synteticky a.click()
        // (stažení PDF) a hned window.open() (WhatsApp). V praxi to ale prohlížeč
        // (Chrome) tiše blokoval, protože JS spuštěný po AJAX/Livewire volání nemá
        // "opravdové" uživatelské gesto, které download/popup vyžaduje. Řešení: dva
        // samostatné, očíslované odkazy – ->url()->openUrlInNewTab() je skutečný <a>
        // rovnou v DOM, klik na něj JE opravdové gesto, funguje spolehlivě stejně
        // jako u ostatních PDF tlačítek.
        $akce = [
            // přijetí zařízení: doklad o převzetí + zpráva
            Action::make('wa_prevzeti_doklad_pdf')
                ->label('1) Stáhnout doklad o převzetí (PDF)')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => $this->record->tiskUrl('doklad'))
                ->openUrlInNewTab(),
            Action::make('wa_prevzeti')
                ->label('2) Otevřít WhatsApp – zařízení převzato (doklad)')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(fn () => $wa($this->zpravaPrevzeti()))
                ->openUrlInNewTab(),

            // hotovo: servisní protokol + zpráva
            Action::make('wa_hotovo_protokol_pdf')
                ->label('1) Stáhnout servisní protokol (PDF)')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => $this->record->tiskUrl('protokol'))
                ->openUrlInNewTab(),
            Action::make('wa_hotovo')
                ->label('2) Otevřít WhatsApp – hotovo k vyzvednutí')
                ->icon('heroicon-o-chat-bubble-left-right')
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

    /** Telefon zákazníka v mezinárodním tvaru bez znaků (např. 420776123456) – pro wa.me. */
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

        // E-mail „zakázka hotová" + rovnou i servisní protokol v příloze (ať zákazník
        // nemusí dostávat dva e-maily – jeden při hotovo, druhý znovu při uzavření).
        if ($zk?->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($zk->email)->send(new \App\Mail\ZakazkaHotova($z));
                Notification::make()->title('E-mail odeslán na ' . $zk->email)->success()->send();
            } catch (\Throwable $e) {
                Notification::make()->title('E-mail se nepodařilo odeslat')
                    ->body('Zkontroluj nastavení pošty (MAIL_* v .env). ' . $e->getMessage())
                    ->danger()->send();
            }

            $this->odesliProtokolEmailem($zk->email);
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
