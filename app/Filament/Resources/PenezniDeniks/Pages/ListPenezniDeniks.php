<?php

namespace App\Filament\Resources\PenezniDeniks\Pages;

use App\Filament\Resources\PenezniDeniks\PenezniDenikResource;
use App\Models\PenezniDenik;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPenezniDeniks extends ListRecords
{
    protected static string $resource = PenezniDenikResource::class;

    protected function getHeaderActions(): array
    {
        $roky = collect(range((int) now()->year, (int) now()->year - 5))
            ->mapWithKeys(fn ($r) => [$r => (string) $r])->all();

        return [
            ActionGroup::make([
                Action::make('export_denik')
                    ->label('Peněžní deník (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->schema([Select::make('rok')->label('Rok')->options($roky)->default((int) now()->year)])
                    ->action(fn (array $data) => redirect()->route('export.denik', [
                        'rok' => $data['rok'],
                        'skupina' => $this->activeTab !== 'vse' ? $this->activeTab : null,
                    ])),
                Action::make('export_dan')
                    ->label('Podklad pro daňové přiznání (CSV)')
                    ->icon('heroicon-o-calculator')
                    ->schema([Select::make('rok')->label('Rok')->options($roky)->default((int) now()->year)])
                    ->action(fn (array $data) => redirect()->route('export.dan', $data['rok'])),
            ])->label('Export' . ($this->activeTab && $this->activeTab !== 'vse'
                ? ' – ' . (PenezniDenik::SKUPINY[$this->activeTab] ?? '')
                : ''))->icon('heroicon-o-arrow-down-tray')->button(),

            CreateAction::make(),
        ];
    }

    /** Záložky (tlačítka) podle skupiny dokladů. */
    public function getTabs(): array
    {
        $tabs = ['vse' => Tab::make('Vše')];

        foreach (PenezniDenik::SKUPINY as $klic => $nazev) {
            $s = $klic;
            $tabs[$klic] = Tab::make($nazev)
                ->badge(PenezniDenik::query()->skupina($klic)->count())
                ->modifyQueryUsing(function (Builder $query) use ($s) {
                    return $query->skupina($s);
                });
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'vse';
    }
}
