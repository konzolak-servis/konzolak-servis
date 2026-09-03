<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

class ZalohaData extends Command
{
    protected $signature = 'zaloha:data {--keep=21 : Kolik posledních záloh nechat}';

    protected $description = 'Záloha databáze a nahraných souborů do storage/zalohy';

    public function handle(): int
    {
        $dir = storage_path('zalohy');
        File::ensureDirectoryExists($dir);

        $stamp = now()->format('Y-m-d_His');
        $sqlPath = $dir . DIRECTORY_SEPARATOR . "db_{$stamp}.sql";
        $zipPath = $dir . DIRECTORY_SEPARATOR . "zaloha_{$stamp}.zip";

        // --- dump databáze ---
        $dumpBin = env('MYSQLDUMP_PATH', 'mysqldump');
        $process = new Process([
            $dumpBin,
            '--host=' . config('database.connections.mysql.host'),
            '--port=' . config('database.connections.mysql.port'),
            '--user=' . config('database.connections.mysql.username'),
            '--password=' . (string) config('database.connections.mysql.password'),
            '--single-transaction', '--skip-lock-tables', '--default-character-set=utf8mb4',
            config('database.connections.mysql.database'),
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('mysqldump selhal: ' . trim($process->getErrorOutput()));

            return self::FAILURE;
        }
        File::put($sqlPath, $process->getOutput());

        // --- zip: dump + nahrané soubory + .env (kvůli APP_KEY / obnovitelnosti) ---
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($sqlPath, basename($sqlPath));

        $envPath = base_path('.env');
        if (is_file($envPath)) {
            $zip->addFile($envPath, 'env/.env');
        }

        $verejne = storage_path('app/public');
        if (is_dir($verejne)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($verejne, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($files as $f) {
                if ($f->isFile()) {
                    $zip->addFile($f->getPathname(), 'soubory/' . str_replace($verejne . DIRECTORY_SEPARATOR, '', $f->getPathname()));
                }
            }
        }
        $zip->close();
        File::delete($sqlPath);

        $mb = round(filesize($zipPath) / 1048576, 2);
        $this->info("Záloha hotová: " . basename($zipPath) . " ({$mb} MB)");

        // --- offsite kopie na Cloudflare R2 (jen pokud je nakonfigurováno) ---
        $this->nahrajNaR2($zipPath);

        // --- úklid starých ---
        $keep = (int) $this->option('keep');
        $stare = collect(File::files($dir))
            ->filter(fn ($f) => str_starts_with($f->getFilename(), 'zaloha_'))
            ->sortByDesc(fn ($f) => $f->getFilename())
            ->slice($keep);
        foreach ($stare as $f) {
            File::delete($f->getPathname());
        }
        if ($stare->count()) {
            $this->line("Smazáno starých záloh: {$stare->count()}");
        }

        return self::SUCCESS;
    }

    /**
     * Nahraje zip na Cloudflare R2 (přes rclone) a nechá tam jen posledních N.
     * Aktivní pouze když je v .env nastaveno ZALOHA_R2_REMOTE (např. "r2:konzolak-zalohy").
     */
    private function nahrajNaR2(string $zipPath): void
    {
        $remote = (string) env('ZALOHA_R2_REMOTE');
        if ($remote === '') {
            return;
        }

        $rclone = (string) env('RCLONE_PATH', 'rclone');
        $config = (string) env('ZALOHA_R2_CONFIG', '');
        $spolecne = $config !== '' ? ['--config', $config] : [];

        $up = new Process([$rclone, 'copy', $zipPath, $remote, '--s3-no-check-bucket', ...$spolecne]);
        $up->setTimeout(600);
        $up->run();

        if (! $up->isSuccessful()) {
            $this->error('R2 upload selhal: ' . trim($up->getErrorOutput()));

            return;
        }
        $this->info('Nahráno na R2: ' . $remote);

        // vzdálený úklid – nechat posledních N (default 60)
        $keepR2 = max(1, (int) env('ZALOHA_R2_KEEP', 60));

        $list = new Process([$rclone, 'lsf', $remote, '--include', 'zaloha_*.zip', ...$spolecne]);
        $list->setTimeout(120);
        $list->run();
        if (! $list->isSuccessful()) {
            return;
        }

        $prebytek = collect(preg_split('/\r?\n/', trim($list->getOutput())))
            ->filter()
            ->sortDesc()
            ->values()
            ->slice($keepR2);

        foreach ($prebytek as $jmeno) {
            $del = new Process([$rclone, 'deletefile', rtrim($remote, '/') . '/' . $jmeno, ...$spolecne]);
            $del->setTimeout(120);
            $del->run();
        }
        if ($prebytek->count()) {
            $this->line("Smazáno starých záloh na R2: {$prebytek->count()}");
        }
    }
}
