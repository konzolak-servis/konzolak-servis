<?php

namespace App\Filament\Resources\Zakazniks\Pages;

use App\Filament\Resources\Zakazniks\ZakaznikResource;
use App\Models\Zakaznik;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditZakaznik extends EditRecord
{
    protected static string $resource = ZakaznikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sloucit')
                ->label('Sloučit s duplicitou')
                ->icon('heroicon-o-arrows-pointing-in')
                ->color('warning')
                ->modalHeading(fn () => 'Sloučit do zákazníka ' . $this->record->nazev)
                ->modalDescription('Zakázky, zařízení, faktury, nabídky a zprávy vybraných zákazníků se převedou sem, chybějící údaje se doplní a duplicity se smažou.')
                ->modalSubmitActionLabel('Sloučit')
                ->schema([
                    Select::make('ostatni')->label('Duplicitní zákazníci')
                        ->multiple()->required()->searchable()
                        ->options(fn () => Zakaznik::podobni($this->record->only(['typ', 'jmeno', 'firma_nazev', 'telefon', 'email']), $this->record->id)
                            ->mapWithKeys(fn ($z) => [$z->id => $z->vyber_label . ' (' . $z->zakazky()->count() . ' zak.)'])->all())
                        ->getSearchResultsUsing(fn (string $search) => collect(Zakaznik::hledej($search))->except($this->record->id)->all())
                        ->getOptionLabelsUsing(fn (array $values) => Zakaznik::whereKey($values)->get()->mapWithKeys(fn ($z) => [$z->id => $z->vyber_label])->all())
                        ->default(fn () => Zakaznik::podobni($this->record->only(['typ', 'jmeno', 'firma_nazev', 'telefon', 'email']), $this->record->id)->pluck('id')->all())
                        ->helperText('Předvybraní jsou zákazníci se stejným jménem, telefonem nebo e-mailem.'),
                ])
                ->action(function (array $data) {
                    $pocet = $this->record->sloucit(Zakaznik::whereKey($data['ostatni'])->whereKeyNot($this->record->id)->get());
                    Notification::make()->success()->title("Sloučeno ({$pocet})")->send();
                    $this->redirect(ZakaznikResource::getUrl('edit', ['record' => $this->record]));
                }),
            DeleteAction::make(),
        ];
    }
}
