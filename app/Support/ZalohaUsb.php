<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Kopie záloh na externí USB disk (ROG STRIX Arion). Disk se hledá podle jmenovky svazku,
 * ne podle písmena – Windows mu může při dalším připojení přidělit jiné.
 * Nastavení: services.zaloha.usb_label (env ZALOHA_USB_LABEL), usb_slozka (ZALOHA_USB_SLOZKA).
 */
class ZalohaUsb
{
    /** Kořen disku (např. "E:\") s danou jmenovkou, nebo null, když není připojený. */
    public static function disk(): ?string
    {
        $label = trim((string) config('services.zaloha.usb_label'));
        if ($label === '' || PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        foreach (range('D', 'Z') as $pismeno) {
            if (! is_dir("{$pismeno}:\\")) {
                continue;
            }
            $vol = new Process(['cmd', '/c', "vol {$pismeno}:"]);
            $vol->setTimeout(10);
            $vol->run();
            // "Volume in drive E is ROG STRIX Arion" / "Svazek v jednotce E je …" – jmenovka je na konci 1. řádku
            $radek = strtok($vol->getOutput(), "\r\n");
            if ($vol->isSuccessful() && $radek !== false && str_ends_with(mb_strtolower(trim($radek)), mb_strtolower($label))) {
                return "{$pismeno}:\\";
            }
        }

        return null;
    }

    /** Cílová složka záloh na USB (vytvoří ji), nebo null, když disk není připojený. */
    public static function slozka(): ?string
    {
        $disk = self::disk();
        if ($disk === null) {
            return null;
        }

        $cesta = $disk . trim((string) config('services.zaloha.usb_slozka', 'Konzolak-zalohy'), '\\/');
        File::ensureDirectoryExists($cesta);

        return $cesta;
    }

    /**
     * Zkopíruje na USB všechny lokální zálohy, které tam ještě nejsou (nebo mají jinou velikost).
     * Na USB se nic nemaže – disk je velký a slouží jako dlouhodobý archiv.
     *
     * @param  iterable<\SplFileInfo>  $zalohy
     * @return array{slozka: string, zkopirovano: int}|null  null = disk není připojený
     */
    public static function synchronizuj(iterable $zalohy): ?array
    {
        $slozka = self::slozka();
        if ($slozka === null) {
            return null;
        }

        $zkopirovano = 0;
        foreach ($zalohy as $f) {
            $cil = $slozka . DIRECTORY_SEPARATOR . $f->getFilename();
            if (is_file($cil) && filesize($cil) === $f->getSize()) {
                continue;
            }
            if (! File::copy($f->getPathname(), $cil)) {
                throw new \RuntimeException('Kopírování na USB selhalo: ' . $f->getFilename());
            }
            $zkopirovano++;
        }

        return ['slozka' => $slozka, 'zkopirovano' => $zkopirovano];
    }
}
