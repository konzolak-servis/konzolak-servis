<?php

namespace App\Support\Eet;

use App\Models\EetTrzba;
use App\Models\Firma;
use App\Models\PenezniDenik;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Evidence tržeb EET 2.0 napojená na peněžní deník.
 *
 * Eviduje se příjem z kontaktní platby: hotově (způsob „hotove") nebo převod/QR přijatý na místě
 * (příznak eet_na_miste). Platba na dálku (převod podle dokladu z domova) se neeviduje.
 * Kauce se při složení ani vrácení neeviduje – eviduje se až její započtení (nájemné, škoda),
 * což v deníku vzniká jako příjem při vyúčtování zápůjčky.
 */
class Eet
{
    public static function zapnuto(): bool
    {
        $f = Firma::get();

        return (bool) $f->eet_aktivni && filled($f->eet_dic) && filled($f->eet_id_jednotky) && filled($f->eet_cert_soubor);
    }

    /** Má se tento záznam deníku evidovat? */
    public static function evidovat(PenezniDenik $d): bool
    {
        $f = Firma::get();
        if (! self::zapnuto() || $d->typ !== 'prijem' || (float) $d->castka <= 0) {
            return false;
        }
        if ($f->eet_od && $d->datum && $d->datum->lt($f->eet_od)) {
            return false;
        }

        return ($d->zpusob === 'hotove' && ! $d->eet_vyjmout) || (bool) $d->eet_na_miste;
    }

    /**
     * Po uložení/smazání záznamu deníku: zajistí, aby evidovaná částka odpovídala.
     * Změna částky = storno původní tržby (záporně) + nová tržba; zrušení příjmu = storno.
     */
    public static function synchronizuj(PenezniDenik $d, bool $smazano = false): void
    {
        if (! self::zapnuto()) {
            return;
        }

        $evidovano = round((float) EetTrzba::where('penezni_denik_id', $d->id)->sum('celk_trzba'), 2);
        $ma = $smazano || ! self::evidovat($d) ? 0.0 : round((float) $d->castka, 2);
        if (abs($ma - $evidovano) < 0.005) {
            return;
        }

        if (abs($evidovano) >= 0.005) {
            self::zalozit($d, -$evidovano, true, 'Storno: ' . $d->popis);
        }
        if ($ma > 0) {
            self::zalozit($d, $ma, false, $d->popis);
        }
    }

    private static function zalozit(PenezniDenik $d, float $castka, bool $storno, ?string $popis): void
    {
        $f = Firma::get();
        $t = EetTrzba::create([
            'penezni_denik_id' => $d->exists ? $d->id : null,
            'popis' => \Illuminate\Support\Str::limit((string) $popis, 250, ''),
            'porad_cis' => 'tmp',
            // okamžik platby: teď (deník má jen datum; u dnešních záznamů je to přesné, starší se zaznamenávají dodatečně)
            'dat_trzby' => EetKlient::cas($d->datum && ! $d->datum->isToday() && ! $storno
                ? $d->datum->copy()->setTimeFrom(now())->min(now()) : now()),
            'celk_trzba' => $castka,
            'storno' => $storno,
            'prostredi' => $f->eet_prostredi,
            'overeni' => (bool) $f->eet_overeni,
        ]);
        // pořadové číslo = rok + id záznamu (unikátní, max 25 znaků)
        $t->update(['porad_cis' => now()->format('Y') . '/' . $t->id]);

        // odeslat hned po uložení transakce (aby pomalá síť nebrzdila uložení formuláře při výpadku)
        \Illuminate\Support\Facades\DB::afterCommit(fn () => self::odeslat($t->fresh()));
    }

    /** Odeslání jedné tržby (první pokus i opakování). */
    public static function odeslat(EetTrzba $t): EetTrzba
    {
        if ($t->stav === 'odeslano') {
            return $t;
        }

        try {
            $k = self::klient($t->prostredi);
        } catch (\Throwable $e) {
            $t->update(['stav' => 'chyba', 'chyba_text' => \Illuminate\Support\Str::limit($e->getMessage(), 250), 'pokusy' => $t->pokusy + 1]);

            return $t;
        }

        $f = Firma::get();
        $r = $k->odeslat([
            'eic_popl' => $f->eet_dic,
            'id_jednotky' => (int) $f->eet_id_jednotky,
            'id_pokl' => $f->eet_id_pokl ?: '1',
            'porad_cis' => $t->porad_cis,
            'dat_trzby' => $t->dat_trzby,
            'celk_trzba' => (float) $t->celk_trzba,
        ], $t->pokusy === 0, (bool) $t->overeni);

        $t->update([
            'stav' => $r['ok'] ? 'odeslano' : ($r['docasna'] ? 'ceka' : 'chyba'),
            'pok' => $r['pok'] ?? null,
            'pokusy' => $t->pokusy + 1,
            'chyba_kod' => $r['chyba_kod'] ?? null,
            'chyba_text' => isset($r['chyba_text']) ? \Illuminate\Support\Str::limit($r['chyba_text'], 250) : null,
            'varovani' => $r['varovani'] ?: null,
            'transakce_id' => $r['transakce_id'] ?? null,
            'odeslano_at' => $r['ok'] ? now() : $t->odeslano_at,
        ]);

        return $t;
    }

    /** Dodatečné odeslání všech neodeslaných (scheduler každou minutu; zákonná lhůta 48 h). */
    public static function odeslatCekajici(): int
    {
        $n = 0;
        EetTrzba::where('stav', 'ceka')->orderBy('id')->limit(50)->get()
            ->each(function (EetTrzba $t) use (&$n) {
                // první minutu po založení nechat na okamžitém pokusu
                if ($t->pokusy > 0 && $t->updated_at && $t->updated_at->gt(now()->subMinute())) {
                    return;
                }
                self::odeslat($t);
                $n++;
            });

        return $n;
    }

    public static function klient(?string $prostredi = null): EetKlient
    {
        $f = Firma::get();
        if (! $f->eet_cert_soubor || ! Storage::disk('local')->exists($f->eet_cert_soubor)) {
            throw new \RuntimeException('Chybí pokladní certifikát (Nastavení firmy → EET).');
        }

        return new EetKlient(
            Storage::disk('local')->get($f->eet_cert_soubor),
            $f->eet_cert_heslo ? Crypt::decryptString($f->eet_cert_heslo) : '',
            $prostredi ?: $f->eet_prostredi,
        );
    }
}
