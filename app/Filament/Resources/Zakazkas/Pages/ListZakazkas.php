<?php

namespace App\Filament\Resources\Zakazkas\Pages;

use App\Filament\Resources\Zakazkas\ZakazkaResource;
use App\Models\Zakazka;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListZakazkas extends ListRecords
{
    protected static string $resource = ZakazkaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nová zakázka'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'otevrene' => Tab::make('Rozpracované')
                ->badge(Zakazka::whereNotIn('stav', ['vydano', 'storno'])->count())
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereNotIn('stav', ['vydano', 'storno']);
                }),
            'ceka' => Tab::make('Čeká na díl')
                ->badge(Zakazka::where('stav', 'ceka_na_dil')->count())
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('stav', 'ceka_na_dil');
                }),
            'hotove' => Tab::make('K vydání')
                ->badge(Zakazka::where('stav', 'hotovo')->count())
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('stav', 'hotovo');
                }),
            'vydane' => Tab::make('Vydané')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('stav', 'vydano');
                }),
            'vse' => Tab::make('Vše'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'otevrene';
    }
}
