<?php

namespace App\Filament\Resources\Zpravas\Pages;

use App\Filament\Resources\Zpravas\ZpravaResource;
use App\Models\Zprava;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListZpravas extends ListRecords
{
    protected static string $resource = ZpravaResource::class;

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
