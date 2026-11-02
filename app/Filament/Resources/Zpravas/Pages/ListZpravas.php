<?php

namespace App\Filament\Resources\Zpravas\Pages;

use App\Filament\Resources\Zpravas\ZpravaResource;
use App\Models\Zakazka;
use App\Models\Zakaznik;
use App\Models\Zprava;
use App\Support\Posta;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;

class ListZpravas extends ListRecords
{
    protected static string $resource = ZpravaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('vse_precteno')
                ->label('Označit vše jako přečtené')
                ->icon('heroicon-o-envelope-open')
                ->color('gray')
                ->visible(fn () => Zprava::neprectene()->exists())
                ->requiresConfirmation()
                ->modalHeading('Označit vše jako přečtené?')
                ->modalDescription(fn () => 'Nepřečtených zpráv: ' . Zprava::neprectene()->count() . '. Spam se nemění.')
                ->modalSubmitActionLabel('Označit')
                ->action(function () {
                    $pocet = Zprava::neprectene()->update(['precteno_at' => now()]);

                    Notification::make()->title('Označeno jako přečtené: ' . $pocet)->success()->send();
                }),

            Action::make('napsat')
                ->label('Napsat e-mail')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->modalHeading('Nový e-mail')
                ->modalSubmitActionLabel('Odeslat')
                ->schema([
                    Select::make('zakazka_id')->label('K zakázce (nepovinné)')
                        ->options(fn () => Zakazka::query()->with('zakaznik')->orderByDesc('id')->limit(200)->get()
                            ->mapWithKeys(fn ($z) => [$z->id => $z->cislo . ' · ' . ($z->zakaznik?->nazev ?? '')])->all())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            $email = $state ? Zakazka::find($state)?->zakaznik?->email : null;
                            if (filled($email)) {
                                $set('pro', $email);
                            }
                        })
                        ->helperText('Vyplní e-mail zákazníka a zpráva se zobrazí u zakázky.'),
                    TextInput::make('pro')->label('Komu')->email()->required()
                        ->datalist(fn () => Zakaznik::query()->whereNotNull('email')->where('email', '!=', '')
                            ->orderBy('email')->pluck('email')->unique()->values()->all()),
                    TextInput::make('predmet')->label('Předmět')->required()->maxLength(200),
                    Textarea::make('telo')->label('Text')->rows(10)->required()
                        ->helperText('Odešle se z ' . config('mail.from.address') . '. Firemní hlavička s logem a podpis se doplní automaticky.'),
                ])
                ->action(function (array $data) {
                    $zakazka = filled($data['zakazka_id'] ?? null) ? Zakazka::find($data['zakazka_id']) : null;
                    $zakaznikId = $zakazka?->zakaznik_id
                        ?? Zakaznik::where('email', $data['pro'])->value('id');

                    $ok = Posta::odesli(
                        pro: trim($data['pro']),
                        predmet: $data['predmet'],
                        telo: $data['telo'],
                        zakazkaId: $zakazka?->id,
                        zakaznikId: $zakaznikId,
                    );

                    $ok
                        ? Notification::make()->title('E-mail odeslán')->success()->send()
                        : Notification::make()->title('E-mail se nepodařilo odeslat')
                            ->body('Zkontroluj nastavení Brevo. Zpráva je uložena v Odeslané.')
                            ->warning()->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'neprectene' => Tab::make('Nepřečtené')
                ->badge(Zprava::neprectene()->count() ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('smer', 'in')->whereNull('precteno_at')->where('spam', false);
                }),
            'prijate' => Tab::make('Přijaté')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('smer', 'in')->where('spam', false);
                }),
            'odeslane' => Tab::make('Odeslané')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('smer', 'out');
                }),
            'spam' => Tab::make('Spam')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('spam', true);
                }),
            'vse' => Tab::make('Vše'),
        ];
    }
}
