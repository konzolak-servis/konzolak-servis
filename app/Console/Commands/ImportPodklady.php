<?php

namespace App\Console\Commands;

use App\Models\Zakaznik;
use App\Models\Zarizeni;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Import zákazníků (a jejich zařízení) ze starých Excel dokladů.
 * Zakázky se nezakládají – jen adresář zákazníků, aby se při nové zakázce
 * vracející se zákazník automaticky doplnil.
 *
 * Spuštění:
 *   php artisan import:podklady "C:\Users\zdene\OneDrive\Plocha\PROJEKTY\servis-app\_excel-podklady"
 */
class ImportPodklady extends Command
{
    protected $signature = 'import:podklady {cesta? : Složka s Excel doklady} {--dry : Jen vypsat, nic neukládat}';

    protected $description = 'Naimportuje kompletní databázi zákazníků ze starých Excel dokladů';

    private int $novychZak = 0;
    private int $doplnenoZak = 0;
    private int $novychZar = 0;

    public function handle(): int
    {
        $cesta = $this->argument('cesta')
            ?: 'C:\\Users\\zdene\\OneDrive\\Plocha\\PROJEKTY\\servis-app\\_excel-podklady';

        if (! is_dir($cesta)) {
            $this->error("Složka neexistuje: {$cesta}");

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry');

        $files = [];
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cesta, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($rii as $f) {
            if (strtolower($f->getExtension()) !== 'xlsx') {
                continue;
            }
            $name = $f->getFilename();
            // vynechat šablony a přehledy
            if (preg_match('/vykaz|mustr|^1_servisni_list|^2_servisni_protokol|^servisni_list|^servisní_list/iu', $name)) {
                continue;
            }
            $files[] = $f->getPathname();
        }
        sort($files);

        $this->info(count($files) . ' souborů ke zpracování' . ($dry ? '  (DRY RUN)' : ''));

        foreach ($files as $path) {
            $cells = $this->cells($path);
            if (! $cells) {
                continue;
            }

            $base = basename($path);

            if (preg_match('/^fa\d/i', $base)) {
                $this->zpracujFakturu($cells, $base, $dry);
            } else {
                $this->zpracujProtokol($cells, $base, $dry);
            }
        }

        $this->newLine();
        $this->table(['', 'Počet'], [
            ['Noví zákazníci', $this->novychZak],
            ['Doplnění stávající', $this->doplnenoZak],
            ['Nová zařízení', $this->novychZar],
            ['Zákazníků celkem v DB', Zakaznik::count()],
        ]);

        return self::SUCCESS;
    }

    // ---- Servisní protokol / nabídka -------------------------------------

    private function zpracujProtokol(array $c, string $base, bool $dry): void
    {
        // jméno: řádek "jméno: ..." (A9 nebo A8 u nabídek)
        $raw = $this->hodnotaZa($c, ['jméno:', 'jmeno:']);
        if (! $raw) {
            $raw = $this->zJmenaSouboru($base);
        }
        $raw = $this->cistiJmeno($raw);
        if (! $raw || Str::contains($raw, '…') || Str::contains($raw, '...')) {
            return;
        }

        $jmeno = $this->serad($raw, $base);
        $telefon = $this->cistiTelefon($this->hodnotaZa($c, ['kontakt:']));

        $zak = $this->ulozZakaznika([
            'typ' => 'osoba',
            'jmeno' => $jmeno,
            'telefon' => $telefon,
        ], $dry);

        if (! $zak) {
            return;
        }

        // Nabídky (PC sestavy) nemají hlavičku "zařízení" – žádná zařízení neimportujeme.
        $hlavickaRadek = null;
        foreach ($c as $ref => $v) {
            if (preg_match('/^(přijaté\s+)?zařízení\s*$/iu', trim($v)) && preg_match('/^A(\d+)$/', $ref, $m)) {
                $hlavickaRadek = (int) $m[1];
                break;
            }
        }
        if ($hlavickaRadek === null) {
            return;
        }

        // zařízení: řádky pod hlavičkou, označení v A, SN v B
        for ($r = $hlavickaRadek + 1; $r <= $hlavickaRadek + 6; $r++) {
            $oznaceni = trim($c["A{$r}"] ?? '');
            if ($oznaceni === '' || mb_strlen($oznaceni) < 3) {
                continue;
            }
            // vynechat řádky, které jsou zjevně práce/ceny/souhrny
            if (preg_match('/^(čištění|cištění|výměna|instalace|oprava|dobírka|práce|celkem)/iu', $oznaceni)) {
                continue;
            }
            $sn = trim($c["B{$r}"] ?? '');
            $this->ulozZarizeni($zak, $oznaceni, $sn, $dry);
        }
    }

    // ---- Faktura -------------------------------------------------------------

    private function zpracujFakturu(array $c, string $base, bool $dry): void
    {
        $lines = [];
        foreach (range(12, 21) as $r) {
            $v = trim($c["C{$r}"] ?? '');
            if ($v !== '') {
                $lines[] = $v;
            }
        }
        if (! $lines) {
            return;
        }

        $ico = null;
        $nazev = array_shift($lines);
        if (preg_match('/I\.?\s*Č\.?\s*([0-9 ]{5,})/iu', $nazev, $m)) {
            $ico = preg_replace('/\D/', '', $m[1]);
            $nazev = array_shift($lines) ?? $nazev;
        }

        $adresa = [];
        $telefon = null;
        $email = null;
        foreach ($lines as $ln) {
            if (preg_match('/ič[:\s]/iu', $ln)) {
                $ico ??= preg_replace('/\D/', '', $ln);
            } elseif (preg_match('/tel/iu', $ln)) {
                $telefon = $this->cistiTelefon($ln);
            } elseif (Str::contains($ln, '@') || preg_match('/email/iu', $ln)) {
                $email = trim(preg_replace('/^.*email[:\s]*/iu', '', $ln)) ?: null;
            } else {
                $adresa[] = rtrim($ln, ', ');
            }
        }

        $firma = (bool) preg_match('/s\.?\s?r\.?\s?o\.?|spol\.|a\.\s?s\.|společenstv|SVJ|z\.\s?s\./iu', $nazev);

        $data = [
            'typ' => $firma ? 'firma' : 'osoba',
            'ico' => $ico,
            'telefon' => $telefon,
            'email' => $email,
        ];
        if ($firma) {
            $data['firma_nazev'] = $this->cistiJmeno($nazev);
        } else {
            $data['jmeno'] = $this->serad($this->cistiJmeno($nazev), $base);
        }

        $mesto = null;
        $ulice = null;
        if ($adresa) {
            $ulice = $adresa[0] ?? null;
            foreach ($adresa as $a) {
                if (preg_match('/\d{3}\s?\d{2}|\d{5}/', $a)) {
                    $mesto = $a;
                }
            }
        }
        $data['ulice'] = $ulice;
        $data['mesto'] = $mesto;

        $this->ulozZakaznika($data, $dry);
    }

    // ---- Ukládání ---------------------------------------------------------

    private function ulozZakaznika(array $data, bool $dry): ?Zakaznik
    {
        $data = array_filter($data, fn ($v) => $v !== null && $v !== '');

        $klicJmeno = $data['firma_nazev'] ?? $data['jmeno'] ?? null;
        if (! $klicJmeno) {
            return null;
        }
        $klic = $this->klic($klicJmeno);

        $existujici = Zakaznik::all()->first(fn (Zakaznik $z) => $this->klic($z->firma_nazev ?: $z->jmeno) === $klic);

        if ($existujici) {
            $zmena = [];
            foreach (['telefon', 'email', 'ico', 'dic', 'ulice', 'mesto', 'psc'] as $pole) {
                if (empty($existujici->{$pole}) && ! empty($data[$pole] ?? null)) {
                    $zmena[$pole] = $data[$pole];
                }
            }
            if ($zmena) {
                $this->line("  ~ {$klicJmeno}: doplněno " . implode(', ', array_keys($zmena)));
                if (! $dry) {
                    $existujici->update($zmena);
                }
                $this->doplnenoZak++;
            }

            return $existujici;
        }

        $this->line("  + {$klicJmeno}" . (isset($data['telefon']) ? "  ({$data['telefon']})" : ''));
        $this->novychZak++;

        if ($dry) {
            return (new Zakaznik($data));
        }

        return Zakaznik::create($data);
    }

    private function ulozZarizeni(Zakaznik $zak, string $oznaceni, string $sn, bool $dry): void
    {
        $oznaceni = trim(preg_replace('/\s+/', ' ', $oznaceni));
        $sn = $sn === '' ? null : trim($sn);

        if ($dry) {
            $exists = false;
        } else {
            $exists = $zak->zarizeni()
                ->where('oznaceni', $oznaceni)
                ->where(fn ($q) => $q->where('seriove_cislo', $sn)->orWhereNull('seriove_cislo'))
                ->exists();
        }
        if ($exists) {
            return;
        }

        $this->line("      → zařízení: {$oznaceni}" . ($sn ? "  [{$sn}]" : ''));
        $this->novychZar++;

        if (! $dry) {
            $zak->zarizeni()->create([
                'kategorie' => $this->kategorie($oznaceni),
                'oznaceni' => $oznaceni,
                'seriove_cislo' => $sn,
            ]);
        }
    }

    // ---- Pomocné ---------------------------------------------------------

    private function cells(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $shared = [];
        if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $x = @simplexml_load_string($ss);
            if ($x) {
                foreach ($x->si as $si) {
                    $t = '';
                    if (isset($si->t)) {
                        $t = (string) $si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $r) {
                            $t .= (string) $r->t;
                        }
                    }
                    $shared[] = $t;
                }
            }
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheet === false) {
            return [];
        }

        $x = @simplexml_load_string($sheet);
        if (! $x) {
            return [];
        }

        $out = [];
        foreach ($x->sheetData->row as $row) {
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $v = (string) $c->v;
                if ((string) $c['t'] === 's' && $v !== '') {
                    $v = $shared[(int) $v] ?? '';
                }
                if (isset($c->is->t)) {
                    $v = (string) $c->is->t;
                }
                if (trim($v) !== '') {
                    $out[$ref] = $v;
                }
            }
        }

        return $out;
    }

    /** Najde hodnotu v buňce začínající jedním z prefixů a prefix odřízne. */
    private function hodnotaZa(array $cells, array $prefixy): ?string
    {
        foreach ($cells as $v) {
            foreach ($prefixy as $p) {
                if (mb_stripos($v, $p) === 0) {
                    return trim(mb_substr($v, mb_strlen($p)));
                }
            }
        }

        return null;
    }

    private function cistiJmeno(?string $s): ?string
    {
        if (! $s) {
            return null;
        }
        $s = trim(preg_replace('/\s+/', ' ', $s));
        $s = trim($s, " \t\n\r\0\x0B-–—,;");

        return $s ?: null;
    }

    private function cistiTelefon(?string $s): ?string
    {
        if (! $s) {
            return null;
        }
        if (preg_match('/x{3,}/i', $s)) {
            return null;
        }
        $s = preg_replace('/^.*?[:]/', '', $s);          // odřízne "tel.:" apod.
        $d = preg_replace('/[^\d+]/', '', $s);
        $d = ltrim($d, '+');
        if (str_starts_with($d, '420') && strlen($d) === 12) {
            $d = substr($d, 3);
        }
        if (strlen($d) < 9) {
            return null;
        }
        if (strlen($d) === 9) {
            return trim(chunk_split($d, 3, ' '));
        }

        return $d;
    }

    private function zJmenaSouboru(string $base): ?string
    {
        $n = preg_replace('/\.xlsx$/i', '', $base);
        $n = preg_replace('/^\d+_\d{4}_\d{2}_\d{2}_?/', '', $n);   // 2_2024_02_03_
        $n = preg_replace('/_(nabidka|hdd|oprava|\d+)$/i', '', $n);
        $n = trim(str_replace('_', ' ', $n));

        return $n ?: null;
    }

    /**
     * Seřadí jméno na tvar "Příjmení Jméno" podle pořadí v názvu souboru
     * (soubor je vždy prijmeni_jmeno).
     */
    private function serad(string $jmeno, string $base): string
    {
        $slovaSoubor = preg_split('/[_\s]+/', strtolower((string) $this->zJmenaSouboru($base)));
        $prijmeniSlug = $this->deburr($slovaSoubor[0] ?? '');

        $slova = preg_split('/\s+/', trim($jmeno));
        if (count($slova) !== 2 || $prijmeniSlug === '') {
            return $jmeno;
        }

        [$a, $b] = $slova;
        $aSlug = $this->deburr($a);
        $bSlug = $this->deburr($b);

        if (str_starts_with($aSlug, $prijmeniSlug) || str_starts_with($prijmeniSlug, $aSlug)) {
            return "$a $b";           // už je Příjmení Jméno
        }
        if (str_starts_with($bSlug, $prijmeniSlug) || str_starts_with($prijmeniSlug, $bSlug)) {
            return "$b $a";           // prohodit
        }

        return $jmeno;
    }

    private function klic(?string $jmeno): string
    {
        $slova = preg_split('/\s+/', $this->deburr((string) $jmeno));
        $slova = array_filter($slova);
        sort($slova);

        return implode(' ', $slova);
    }

    private function deburr(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(
            ['á', 'č', 'ď', 'é', 'ě', 'í', 'ň', 'ó', 'ř', 'š', 'ť', 'ú', 'ů', 'ý', 'ž'],
            ['a', 'c', 'd', 'e', 'e', 'i', 'n', 'o', 'r', 's', 't', 'u', 'u', 'y', 'z'],
            $s
        );

        return preg_replace('/[^a-z0-9 ]/', '', $s);
    }

    private function kategorie(string $o): string
    {
        return match (true) {
            (bool) preg_match('/dualsense|dualshock|ovlada[čc]|controller/iu', $o) => 'ovladac',
            (bool) preg_match('/\bPS5\b|playstation 5|CFI-/iu', $o) => 'PS5',
            (bool) preg_match('/\bPS4\b|playstation 4|CUH-/iu', $o) => 'PS4',
            (bool) preg_match('/\bPS3\b/iu', $o) => 'PS3',
            (bool) preg_match('/switch/iu', $o) => 'Switch',
            (bool) preg_match('/xbox/iu', $o) => 'Xbox',
            (bool) preg_match('/\bPC\b|noteb|desktop/iu', $o) => 'PC',
            default => 'jine',
        };
    }
}
