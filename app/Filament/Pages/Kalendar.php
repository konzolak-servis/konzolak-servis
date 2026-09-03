<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Zakazkas\ZakazkaResource;
use App\Models\Zakazka;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

class Kalendar extends Page
{
    protected string $view = 'filament.pages.kalendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Servis';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Kalendář';

    protected static ?string $title = 'Kalendář zakázek';

    /** Zobrazený měsíc jako Y-m. */
    public string $mesic = '';

    public function mount(): void
    {
        $this->mesic = now()->format('Y-m');
    }

    public function predchozi(): void
    {
        $this->mesic = $this->zacatek()->subMonthNoOverflow()->format('Y-m');
    }

    public function dalsi(): void
    {
        $this->mesic = $this->zacatek()->addMonthNoOverflow()->format('Y-m');
    }

    public function dnes(): void
    {
        $this->mesic = now()->format('Y-m');
    }

    protected function zacatek(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->mesic . '-01')->startOfDay();
    }

    public function getNadpisMesiceProperty(): string
    {
        Carbon::setLocale('cs');

        return ucfirst($this->zacatek()->translatedFormat('F Y'));
    }

    /** @return array<int, array<int, array>> týdny → dny */
    public function getTydnyProperty(): array
    {
        $zacatekMesice = $this->zacatek();
        $konecMesice = $zacatekMesice->copy()->endOfMonth();

        // pondělní začátek mřížky
        $od = $zacatekMesice->copy()->startOfWeek(Carbon::MONDAY);
        $do = $konecMesice->copy()->endOfWeek(Carbon::SUNDAY);

        $zakazky = Zakazka::with(['zakaznik', 'zarizeni'])
            ->whereBetween('datum_prijeti', [$od->toDateString(), $do->toDateString()])
            ->get()
            ->groupBy(fn (Zakazka $z) => $z->datum_prijeti->toDateString());

        $tydny = [];
        $den = $od->copy();

        while ($den <= $do) {
            $tyden = [];
            for ($i = 0; $i < 7; $i++) {
                $klic = $den->toDateString();
                $tyden[] = [
                    'datum' => $den->copy(),
                    'v_mesici' => $den->month === $zacatekMesice->month,
                    'dnes' => $den->isToday(),
                    'zakazky' => collect($zakazky->get($klic, []))->map(fn (Zakazka $z) => [
                        'cislo' => $z->cislo,
                        'kdo' => $z->zakaznik?->nazev,
                        'co' => $z->zarizeni?->oznaceni ?? $z->popis_zavady,
                        'stav' => $z->stav_nazev,
                        'barva' => $z->stavBarva(),
                        'hotovo' => $z->jeHotovo(),
                        'dil_objednany' => $z->dil_objednany,
                        'url' => ZakazkaResource::getUrl('edit', ['record' => $z]),
                    ]),
                ];
                $den->addDay();
            }
            $tydny[] = $tyden;
        }

        return $tydny;
    }
}
