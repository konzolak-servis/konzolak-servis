<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenezniDenik extends Model
{
    protected $table = 'penezni_denik';

    protected $guarded = [];

    protected $casts = [
        'datum' => 'date',
        'castka' => 'decimal:2',
    ];

    /** Skupiny pro záložky / filtrovaný export. */
    public const SKUPINY = [
        'opravy' => 'Opravy',
        'faktury' => 'Faktury',
        'bazar' => 'Bazar (výkup/prodej)',
        'material' => 'Nákup dílů',
        'ostatni' => 'Ostatní',
    ];

    public function scopeSkupina($query, ?string $skupina)
    {
        return match ($skupina) {
            'opravy' => $query->whereIn('zdroj', ['zakazka', 'zakazka_zaloha']),
            'faktury' => $query->where('zdroj', 'faktura'),
            'bazar' => $query->where('zdroj', 'obchod'),
            'material' => $query->where('zdroj', 'nakup'),
            'ostatni' => $query->whereNull('zdroj')->orWhere('zdroj', 'rucne'),
            default => $query,
        };
    }

    /** [popisek, url] zdrojového dokladu. */
    public function doklad(): array
    {
        return match ($this->zdroj) {
            'zakazka', 'zakazka_zaloha' => $this->odkaz(Zakazka::class,
                \App\Filament\Resources\Zakazkas\ZakazkaResource::class),
            'faktura' => $this->odkaz(Faktura::class,
                \App\Filament\Resources\Fakturas\FakturaResource::class),
            'nakup' => $this->odkaz(Nakup::class,
                \App\Filament\Resources\Nakups\NakupResource::class),
            'obchod' => $this->odkaz(Obchod::class,
                \App\Filament\Resources\Obchods\ObchodResource::class),
            default => [$this->kde ?: null, null],
        };
    }

    private function odkaz(string $model, string $resource): array
    {
        $rec = $model::find($this->zdroj_id);

        return $rec ? [$rec->cislo, $resource::getUrl('edit', ['record' => $rec])] : [null, null];
    }
}
