<?php

namespace App\Filament\Resources\Zpravas\Pages;

use App\Filament\Resources\Zpravas\ZpravaResource;
use App\Models\Zakazka;
use App\Models\Zprava;
use App\Support\Posta;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ViewZprava extends ViewRecord
{
    protected static string $resource = ZpravaResource::class;

    protected string $view = 'filament.resources.zprava-view';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->smer === 'in' && ! $this->record->jePrectena()) {
            $this->record->update(['precteno_at' => now()]);
        }
    }

    public function getTitle(): string
    {
        return Str::of((string) $this->record->predmet)->limit(70);
    }

    /** Celé vlákno konverzace – příchozí i odchozí zprávy chronologicky. */
    public function getVlakno()
    {
        $z = $this->record;

        $q = Zprava::query()->where('spam', false);

        if ($z->zakazka_id) {
            $q->where('zakazka_id', $z->zakazka_id);
        } else {
            $norm = trim((string) preg_replace('/^(re|fwd|fw)\s*:\s*/i', '', (string) $z->predmet));
            $q->where(function ($w) use ($norm, $z) {
                $w->where('id', $z->id)
                    ->orWhere('predmet', 'like', '%' . $norm . '%')
                    ->orWhere('od', $z->od)
                    ->orWhere('pro', $z->od);
            });
        }

        return $q->orderBy('datum')->orderBy('id')->get();
    }

    public function getZakazkaUrl(): ?string
    {
        return $this->record->zakazka
            ? \App\Filament\Resources\Zakazkas\ZakazkaResource::getUrl('edit', ['record' => $this->record->zakazka_id])
            : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('odpovedet')
                ->label('Odpovědět')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('primary')
                ->visible(fn () => $this->record->smer === 'in' && filled($this->record->od))
                ->schema([
                    TextInput::make('predmet')->label('Předmět')->required()
                        ->default(fn () => Str::startsWith(mb_strtolower($this->record->predmet ?? ''), 're:')
                            ? $this->record->predmet
                            : 'Re: ' . $this->record->predmet),
                    Textarea::make('telo')->label('Text')->rows(10)->required()
                        ->helperText('Firemní hlavička s logem a podpis se doplní automaticky.'),
                ])
                ->action(function (array $data) {
                    $ok = Posta::odesli(
                        pro: $this->record->od,
                        predmet: $data['predmet'],
                        telo: $data['telo'],
                        odesilatel: $this->record->schranka ?: config('mail.from.address'),
                        odpovedNa: $this->record,
                        zakazkaId: $this->record->zakazka_id,
                    );

                    $ok
                        ? Notification::make()->title('Odpověď odeslána')->success()->send()
                        : Notification::make()->title('E-mail se nepodařilo odeslat')
                            ->body('Zkontroluj nastavení Brevo. Zpráva je uložena v Odeslané.')
                            ->warning()->send();
                }),

            Action::make('prirad')
                ->label($this->record->zakazka_id ? 'Změnit zakázku' : 'Přiřadit k zakázce')
                ->icon('heroicon-o-link')
                ->color('gray')
                ->schema([
                    Select::make('zakazka_id')->label('Zakázka')
                        ->options(fn () => Zakazka::query()->orderByDesc('id')->limit(200)
                            ->get()->mapWithKeys(fn ($z) => [$z->id => $z->cislo . ' · ' . ($z->zakaznik?->nazev ?? '')])->all())
                        ->searchable()
                        ->default(fn () => $this->record->zakazka_id),
                ])
                ->action(function (array $data) {
                    $zakazka = $data['zakazka_id'] ? Zakazka::find($data['zakazka_id']) : null;

                    $this->record->update([
                        'zakazka_id' => $zakazka?->id,
                        'zakaznik_id' => $zakazka?->zakaznik_id ?? $this->record->zakaznik_id,
                    ]);

                    Notification::make()->title($zakazka ? 'Přiřazeno k ' . $zakazka->cislo : 'Přiřazení zrušeno')
                        ->success()->send();
                }),

            Action::make('precteno')
                ->label(fn () => $this->record->jePrectena() ? 'Označit nepřečtené' : 'Označit přečtené')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->visible(fn () => $this->record->smer === 'in')
                ->action(fn () => $this->record->update([
                    'precteno_at' => $this->record->jePrectena() ? null : now(),
                ])),
        ];
    }
}
