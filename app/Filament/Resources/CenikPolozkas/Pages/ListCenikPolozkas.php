<?php

namespace App\Filament\Resources\CenikPolozkas\Pages;

use App\Filament\Resources\CenikPolozkas\CenikPolozkaResource;
use App\Models\CenikPolozka;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ListCenikPolozkas extends ListRecords
{
    protected static string $resource = CenikPolozkaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /** Kategorie jako záložky (tlačítka) nad seznamem. */
    public function getTabs(): array
    {
        $tabs = [
            'vse' => Tab::make('Vše')->badge(CenikPolozka::count()),
        ];

        $kategorie = CenikPolozka::query()
            ->whereNotNull('kategorie')
            ->selectRaw('kategorie, COUNT(*) as pocet')
            ->groupBy('kategorie')
            ->orderBy('kategorie')
            ->pluck('pocet', 'kategorie');

        foreach ($kategorie as $nazev => $pocet) {
            $kat = (string) $nazev;
            $tabs[Str::slug($kat) ?: $kat] = Tab::make(\App\Support\Platformy::label($kat))
                ->badge($pocet)
                ->modifyQueryUsing(function (Builder $query) use ($kat): Builder {
                    return $query->where('kategorie', $kat);
                });
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'vse';
    }
}
