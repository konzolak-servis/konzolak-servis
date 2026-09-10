<?php

namespace App\Filament\Resources\Obchods\RelationManagers;

use App\Models\BazarNaklad;
use App\Models\SkladPolozka;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NakladyRelationManager extends RelationManager
{
    protected static string $relationship = 'naklady';

    protected static ?string $title = 'Investováno do zařízení (díly a náklady)';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nazev')->label('Název')->required()->maxLength(255),
            TextInput::make('mnozstvi')->label('Množství')->numeric()->default(1)->required(),
            TextInput::make('cena_ks')->label('Cena / ks')->numeric()->default(0)->required()->suffix('Kč'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nazev')
            ->columns([
                TextColumn::make('nazev')->label('Položka')->wrap(),
                TextColumn::make('mnozstvi')->label('Ks')->numeric(),
                TextColumn::make('cena_ks')->label('Cena/ks')->money('CZK'),
                TextColumn::make('cena_celkem')->label('Celkem')->money('CZK')
                    ->state(fn (BazarNaklad $r) => $r->cena_celkem)
                    ->summarize(Summarizer::make()
                        ->label('Náklady na díly')
                        ->using(fn ($query) => $query->get()->sum(fn ($r) => $r->cena_celkem))
                        ->money('CZK')),
                TextColumn::make('skladPolozka.nazev')->label('Zdroj')->badge()->color('info')
                    ->placeholder('ruční náklad'),
                TextColumn::make('datum')->label('Datum')->date('d.m.Y'),
            ])
            ->headerActions([
                Action::make('vyskladnit')
                    ->label('Vyskladnit díl ze skladu')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->schema([
                        Select::make('sklad_polozka_id')
                            ->label('Díl ze skladu')
                            ->options(fn () => SkladPolozka::query()
                                ->where('aktivni', true)
                                ->where('kategorie', '!=', 'Bazar')
                                ->where('mnozstvi_skladem', '>', 0)
                                ->orderBy('nazev')->get()
                                ->mapWithKeys(fn ($s) => [$s->id => "{$s->nazev} — skladem {$s->mnozstvi_skladem} ks, {$s->cena_ks_prumer} Kč/ks"])
                                ->all())
                            ->searchable()->required(),
                        TextInput::make('mnozstvi')->label('Počet ks')->numeric()->default(1)->required()->minValue(0.001),
                    ])
                    ->action(function (array $data, $livewire) {
                        $part = SkladPolozka::find($data['sklad_polozka_id']);
                        $mn = (float) $data['mnozstvi'];
                        if (! $part || $mn <= 0) {
                            return;
                        }
                        if ((float) $part->mnozstvi_skladem < $mn) {
                            Notification::make()->title('Na skladě je jen '.$part->mnozstvi_skladem.' ks')->danger()->send();

                            return;
                        }
                        $obchod = $livewire->getOwnerRecord();
                        $part->vydej($mn, ['zdroj' => 'bazar', 'poznamka' => 'Do bazaru '.$obchod->cislo]);
                        $obchod->naklady()->create([
                            'sklad_polozka_id' => $part->id,
                            'nazev' => $part->nazev,
                            'mnozstvi' => $mn,
                            'cena_ks' => (float) $part->cena_ks_prumer,
                            'datum' => now()->toDateString(),
                            'poznamka' => 'Vyskladněno ze skladu dílů',
                        ]);
                        Notification::make()->title('Díl přidán k nákladům zařízení')->success()->send();
                    }),
                CreateAction::make()
                    ->label('Přidat jiný náklad')
                    ->icon('heroicon-o-plus')
                    ->color('gray')
                    ->modalHeading('Ruční náklad (bez skladu)'),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->modalDescription(fn (BazarNaklad $r) => $r->sklad_polozka_id
                        ? 'Díl se vrátí zpět na sklad.'
                        : 'Náklad se odstraní.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
