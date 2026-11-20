<?php

namespace App\Console\Commands;

use App\Support\Eet\Eet;
use Illuminate\Console\Command;

/** Dodatečné odeslání tržeb, které se nepodařilo odeslat hned (výpadek spojení); zákonná lhůta 48 h. */
class EetOdeslat extends Command
{
    protected $signature = 'eet:odeslat';

    protected $description = 'Odešle do EET tržby čekající na odeslání';

    public function handle(): int
    {
        $n = Eet::odeslatCekajici();
        if ($n) {
            $this->info("Odesláno pokusů: {$n}");
        }

        return self::SUCCESS;
    }
}
