<?php

namespace App\Filament\Resources\SkladPolozkas\Pages;

use App\Filament\Resources\SkladPolozkas\SkladPolozkaResource;
use App\Models\SkladPolozka;
use App\Support\Platformy;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSkladPolozkas extends ListRecords
{
    protected static string $resource = SkladPolozkaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_sklad')
                ->label('Export skladu (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('export.sklad'))
                ->openUrlInNewTab(),

            CreateAction::make(),
        ];
    }

    /**
     * Záložky: díly (všechny + po platformách), pak zvlášť spotřební materiál a nářadí.
     * Počty bez bazaru – stejně jako tabulka.
     */
    public function getTabs(): array
    {
        $zaklad = fn () => SkladPolozka::query()
            ->where(fn ($q) => $q->where('kategorie', '!=', 'Bazar')->orWhereNull('kategorie'));
        $jenDily = fn (Builder $query) => $query->where('druh', 'dil');

        $pocty = $zaklad()->where('druh', 'dil')
            ->selectRaw('platforma, COUNT(*) as pocet')
            ->groupBy('platforma')
            ->pluck('pocet', 'platforma');

        $tabs = ['vse' => Tab::make('Díly')
            ->badge($zaklad()->where('druh', 'dil')->count())
            ->modifyQueryUsing($jenDily)];

        foreach (Platformy::HODNOTY as $klic => $popisek) {
            if (! isset($pocty[$klic])) {
                continue;
            }

            $tabs[$klic] = Tab::make($popisek)
                ->badge($pocty[$klic])
                ->modifyQueryUsing(fn (Builder $query) => $jenDily($query)->where('platforma', $klic));
        }

        $bezPlatformy = (int) ($pocty[''] ?? 0) + (int) ($pocty[null] ?? 0);
        if ($bezPlatformy > 0) {
            $tabs['bez'] = Tab::make('Bez platformy')
                ->badge($bezPlatformy)
                ->modifyQueryUsing(fn (Builder $query) => $jenDily($query)->where(fn ($q) => $q->whereNull('platforma')->orWhere('platforma', '')));
        }

        foreach (['spotrebni' => 'Spotřební materiál', 'naradi' => 'Nářadí'] as $druh => $popisek) {
            $tabs[$druh] = Tab::make($popisek)
                ->icon($druh === 'naradi' ? 'heroicon-o-wrench-screwdriver' : 'heroicon-o-beaker')
                ->badge($zaklad()->where('druh', $druh)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('druh', $druh));
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'vse';
    }
}
