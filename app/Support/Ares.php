<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/** Vyhledání firmy podle IČO v registru ARES (Ministerstvo financí, veřejné REST API bez klíče). */
class Ares
{
    private const URL = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/';

    /** Normalizuje IČO na 8 číslic (doplní úvodní nuly); null, když nevypadá jako IČO. */
    public static function normalizuj(?string $ico): ?string
    {
        $cisla = preg_replace('/\D/', '', (string) $ico);
        if ($cisla === '' || strlen($cisla) > 8) {
            return null;
        }

        return str_pad($cisla, 8, '0', STR_PAD_LEFT);
    }

    /** Kontrola IČO (modulo 11) – odhalí překlep dřív, než se zeptáme registru. */
    public static function platne(string $ico): bool
    {
        if (! preg_match('/^\d{8}$/', $ico)) {
            return false;
        }
        $soucet = 0;
        for ($i = 0; $i < 7; $i++) {
            $soucet += (int) $ico[$i] * (8 - $i);
        }
        $zbytek = $soucet % 11;
        $kontrolni = match ($zbytek) {
            0 => 1,
            1 => 0,
            default => 11 - $zbytek,
        };

        return $kontrolni === (int) $ico[7];
    }

    /**
     * @return array{ico:string,nazev:string,dic:?string,ulice:?string,psc:?string,mesto:?string}
     *
     * @throws \RuntimeException s textem srozumitelným pro uživatele
     */
    public static function najdi(?string $vstup): array
    {
        $ico = self::normalizuj($vstup);
        if ($ico === null || ! self::platne($ico)) {
            throw new \RuntimeException('IČO nevypadá správně. Zkontroluj, že má 8 číslic.');
        }

        try {
            $r = Http::timeout(10)->acceptJson()->get(self::URL . $ico);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Registr ARES teď neodpovídá. Zkus to za chvíli nebo údaje vyplň ručně.');
        }

        if ($r->status() === 404) {
            throw new \RuntimeException('Firma s IČO ' . $ico . ' nebyla v ARES nalezena.');
        }
        if (! $r->successful() || ! is_array($r->json()) || ! $r->json('obchodniJmeno')) {
            throw new \RuntimeException('Registr ARES vrátil neočekávanou odpověď. Údaje vyplň ručně.');
        }

        $s = (array) $r->json('sidlo', []);
        $ulice = null;
        if (! empty($s['nazevUlice'])) {
            $cisla = trim(($s['cisloDomovni'] ?? '') . (isset($s['cisloOrientacni'])
                ? '/' . $s['cisloOrientacni'] . ($s['cisloOrientacniPismeno'] ?? '')
                : ''), ' /');
            $ulice = trim($s['nazevUlice'] . ' ' . $cisla);
        } elseif (! empty($s['nazevCastiObce']) || ! empty($s['cisloDomovni'])) {
            // obec bez ulic: „Část obce č. p."
            $ulice = trim(($s['nazevCastiObce'] ?? $s['nazevObce'] ?? '') . ' ' . ($s['cisloDomovni'] ?? ''));
        }
        $psc = ! empty($s['psc']) ? preg_replace('/^(\d{3})(\d{2})$/', '$1 $2', str_pad((string) $s['psc'], 5, '0', STR_PAD_LEFT)) : null;

        return [
            'ico' => $ico,
            'nazev' => (string) $r->json('obchodniJmeno'),
            'dic' => $r->json('dic') ?: null,
            'ulice' => $ulice ?: null,
            'psc' => $psc,
            'mesto' => $s['nazevObce'] ?? null,
        ];
    }
}
