<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Aktuální počasí pro Zlín z Open-Meteo (bez klíče, zdarma). Cache 30 min.
 */
class Pocasi
{
    public const LAT = 49.2331;
    public const LON = 17.6669;
    public const MISTO = 'Zlín';

    public static function aktualni(): ?array
    {
        return Cache::remember('pocasi_zlin', now()->addMinutes(30), function () {
            try {
                $r = Http::timeout(6)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => self::LAT,
                    'longitude' => self::LON,
                    'current' => 'temperature_2m,apparent_temperature,weather_code,wind_speed_10m',
                    'timezone' => 'Europe/Prague',
                ]);

                if (! $r->ok()) {
                    return null;
                }

                $c = $r->json('current');
                [$popis, $ikona] = self::popisKodu((int) ($c['weather_code'] ?? -1));

                return [
                    'teplota' => round($c['temperature_2m'] ?? 0),
                    'pocit' => round($c['apparent_temperature'] ?? 0),
                    'vitr' => round($c['wind_speed_10m'] ?? 0),
                    'popis' => $popis,
                    'ikona' => $ikona,
                    'misto' => self::MISTO,
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }

    private static function popisKodu(int $kod): array
    {
        return match (true) {
            $kod === 0 => ['Jasno', '☀️'],
            in_array($kod, [1, 2]) => ['Polojasno', '🌤️'],
            $kod === 3 => ['Zataženo', '☁️'],
            in_array($kod, [45, 48]) => ['Mlha', '🌫️'],
            in_array($kod, [51, 53, 55, 56, 57] , true) => ['Mrholení', '🌦️'],
            in_array($kod, [61, 63, 65, 66, 67], true) => ['Déšť', '🌧️'],
            in_array($kod, [71, 73, 75, 77], true) => ['Sněžení', '🌨️'],
            in_array($kod, [80, 81, 82], true) => ['Přeháňky', '🌦️'],
            in_array($kod, [85, 86], true) => ['Sněhové přeháňky', '🌨️'],
            in_array($kod, [95, 96, 99], true) => ['Bouřka', '⛈️'],
            default => ['—', '🌡️'],
        };
    }
}
