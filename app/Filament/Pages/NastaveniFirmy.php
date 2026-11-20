<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\JenProAdmina;
use App\Models\Firma;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class NastaveniFirmy extends Page
{
    use JenProAdmina;

    protected string $view = 'filament.pages.nastaveni-firmy';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Nastavení';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Nastavení firmy';

    protected static ?string $title = 'Nastavení firmy';

    public ?array $data = [];

    public function mount(): void
    {
        $data = Firma::get()->attributesToArray();
        unset($data['eet_cert_heslo']); // šifrované heslo do formuláře nepatří
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Údaje firmy')
                    ->description('Zobrazují se v hlavičce všech dokladů.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nazev')->label('Název')->required(),
                        TextInput::make('ico')->label('IČO'),
                        TextInput::make('dic')->label('DIČ'),
                        TextInput::make('cislo_uctu')->label('Číslo účtu'),
                        TextInput::make('ulice')->label('Ulice a č. p.'),
                        TextInput::make('psc')->label('PSČ'),
                        TextInput::make('mesto')->label('Město'),
                        TextInput::make('telefon')->label('Telefon')->tel(),
                        TextInput::make('email')->label('E-mail')->email(),
                        TextInput::make('web')->label('Web'),
                        TextInput::make('osloveni')->label('Oslovení na nástěnce (5. pád)')
                            ->placeholder('Zdeňku')
                            ->helperText('Nepovinné – když necháš prázdné, odhadne se z tvého jména.'),
                        Toggle::make('platce_dph')->label('Plátce DPH'),
                        TextInput::make('splatnost_dni')->label('Splatnost faktur (dní)')->numeric(),
                        TextInput::make('zaruka_mesice')->label('Výchozí záruka (měsíců)')->numeric(),
                    ]),

                Section::make('EET 2.0 – evidence tržeb')
                    ->description('Od 1. 1. 2027. Do EET se automaticky odesílá každý příjem z peněžního deníku zaplacený na místě (hotově, nebo QR/převodem přímo u tebe). Platby převodem na dálku a dobírka se neodesílají. Údaje získáš v portálu MOJE daně → Evidence tržeb.')
                    ->collapsible()
                    ->columns(3)
                    ->schema([
                        Toggle::make('eet_aktivni')->label('Odesílat tržby do EET')->inline(false),
                        \Filament\Forms\Components\Radio::make('eet_prostredi')->label('Prostředí')
                            ->options(['produkce' => 'Ostré (produkce)', 'playground' => 'Testovací (playground)'])
                            ->default('playground'),
                        Toggle::make('eet_overeni')->label('Ověřovací režim')->inline(false)
                            ->helperText('Jen zkouška spojení – tržby se NEevidují. Pro ostrý provoz vypnout.'),
                        TextInput::make('eet_dic')->label('DIČ (EIČ)')->placeholder('CZ1234567890')
                            ->regex('/^CZ[0-9]{8,10}$/')->helperText('Musí odpovídat certifikátu.'),
                        TextInput::make('eet_id_jednotky')->label('ID evidenční jednotky')->numeric()
                            ->helperText('Číslo provozovny z DIS+ (končí 1–4).'),
                        TextInput::make('eet_id_pokl')->label('Označení pokladny')->maxLength(20)->default('1'),
                        \Filament\Forms\Components\DatePicker::make('eet_od')->label('Evidovat příjmy od')->native(false)
                            ->default('2027-01-01')->helperText('Starší příjmy se neodesílají.'),
                        \Filament\Forms\Components\FileUpload::make('eet_cert_soubor')->label('Pokladní certifikát (.p12)')
                            ->disk('local')->directory('eet')->visibility('private')
                            ->acceptedFileTypes(['application/x-pkcs12', 'application/octet-stream', '.p12', '.pfx'])
                            ->maxSize(64),
                        TextInput::make('eet_cert_heslo')->label('Heslo k certifikátu')
                            ->password()->revealable()
                            ->placeholder('uložené – vyplň jen při změně')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Crypt::encryptString($state)),
                    ])
                    ->footerActions([
                        Action::make('eet_test')->label('Otestovat spojení s EET')->icon('heroicon-o-signal')
                            ->action(fn () => $this->otestovatEet()),
                    ]),

                Section::make('Automatické texty na dokladech')
                    ->description('Právní / informační text v patě jednotlivých dokumentů. Uprav podle potřeby – projeví se okamžitě při dalším tisku.')
                    ->schema([
                        Textarea::make('pravni_text_servisni_list')
                            ->label('Doklad o převzetí zařízení (do opravy)')->rows(4),
                        Textarea::make('pravni_text_protokol')
                            ->label('Servisní protokol (dokončení opravy)')->rows(4),
                        Textarea::make('pravni_text_faktura')
                            ->label('Faktura')->rows(3),
                        Textarea::make('pravni_text_nabidka')
                            ->label('Cenová nabídka / PC sestava')->rows(3),
                        Textarea::make('zapujcka_pravidla')
                            ->label('Pravidla zápůjčky (předávací protokol)')->rows(14)
                            ->helperText('Každý řádek = jedno očíslované pravidlo. {telefon} se nahradí telefonem firmy.'),
                    ]),
            ]);
    }

    /** Uloží nastavení a pošle zkušební zprávu v ověřovacím režimu (nic se neeviduje). */
    public function otestovatEet(): void
    {
        $this->save();
        $f = Firma::get();

        try {
            $k = \App\Support\Eet\Eet::klient();
            $r = $k->odeslat([
                'eic_popl' => $f->eet_dic ?: (string) $k->eicCertifikatu(),
                'id_jednotky' => (int) ($f->eet_id_jednotky ?: 11),
                'id_pokl' => $f->eet_id_pokl ?: '1',
                'porad_cis' => 'TEST/' . now()->format('YmdHis'),
                'dat_trzby' => \App\Support\Eet\EetKlient::cas(now()),
                'celk_trzba' => 1.00,
            ], true, true);

            $info = 'Certifikát: ' . $k->eicCertifikatu() . ', platný do ' . $k->platnostDo()?->format('d.m.Y')
                . ($f->eet_dic && $f->eet_dic !== $k->eicCertifikatu() ? '. POZOR: DIČ v nastavení se neshoduje s certifikátem!' : '')
                . (! empty($r['varovani']) ? '. Varování: ' . collect($r['varovani'])->pluck('text')->implode('; ') : '');

            $r['ok']
                ? Notification::make()->success()->title('Spojení s EET funguje (' . ($f->eet_prostredi === 'produkce' ? 'ostré' : 'testovací') . ' prostředí)')->body($info)->persistent()->send()
                : Notification::make()->danger()->title('EET odmítlo zkušební zprávu')->body(($r['chyba_kod'] ?? '') . ' ' . ($r['chyba_text'] ?? '') . '. ' . $info)->persistent()->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()->title('Test EET se nepodařil')->body($e->getMessage())->persistent()->send();
        }
    }

    public function save(): void
    {
        Firma::get()->update($this->form->getState());

        Notification::make()->title('Nastavení uloženo')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Uložit nastavení')->submit('save'),
        ];
    }
}
