<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\JenProAdmina;
use App\Models\Firma;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PostaNastaveni extends Page
{
    use JenProAdmina;

    protected string $view = 'filament.pages.posta-nastaveni';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Pošta';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Nastavení pošty';

    protected static ?string $title = 'Nastavení pošty';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Firma::get()->only(['email', 'podpis_email', 'email_vyzvednuti']));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Odesílání')
                    ->description('Odchozí e-maily (odpovědi v Poště, „zakázka hotová", protokoly) chodí přes Brevo.')
                    ->schema([
                        TextInput::make('email')
                            ->label('Adresa odesílatele / pro odpovědi')
                            ->email()
                            ->helperText('Zobrazí se zákazníkovi jako odesílatel. Musí to být adresa @konzolak.com.'),
                    ]),

                Section::make('Podpis')
                    ->description('Automaticky se připojí pod text každé odpovědi odeslané z Pošty.')
                    ->schema([
                        Textarea::make('podpis_email')
                            ->label('Podpis e-mailu')
                            ->rows(6)
                            ->placeholder("S pozdravem\nKonzolák Zlín\ntel. 773 001 488"),
                    ]),

                Section::make('Text o vyzvednutí zařízení')
                    ->description('Zobrazí se v e-mailu „zakázka je hotová" pod adresou provozovny.')
                    ->schema([
                        Textarea::make('email_vyzvednuti')
                            ->label('Informace k vyzvednutí')
                            ->rows(5),
                    ]),
            ]);
    }

    public function save(): void
    {
        Firma::get()->update($this->form->getState());

        Notification::make()->title('Nastavení pošty uloženo')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Uložit')->submit('save'),
        ];
    }
}
