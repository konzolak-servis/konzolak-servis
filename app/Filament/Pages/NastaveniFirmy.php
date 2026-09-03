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
        $this->form->fill(Firma::get()->attributesToArray());
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

                Section::make('Automatické texty na dokladech')
                    ->description('Právní / informační text v patě jednotlivých dokumentů. Uprav podle potřeby – projeví se okamžitě při dalším tisku.')
                    ->schema([
                        Textarea::make('pravni_text_servisni_list')
                            ->label('Servisní doklad (převzetí do opravy)')->rows(4),
                        Textarea::make('pravni_text_protokol')
                            ->label('Servisní protokol (dokončení opravy)')->rows(4),
                        Textarea::make('pravni_text_faktura')
                            ->label('Faktura')->rows(3),
                        Textarea::make('pravni_text_nabidka')
                            ->label('Cenová nabídka / PC sestava')->rows(3),
                    ]),
            ]);
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
