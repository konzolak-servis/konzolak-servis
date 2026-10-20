<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // dopravce byl volný text; sjednotit běžné zápisy na názvy z nabídky
        foreach (['Zásilkovna' => ['zasilkovna', 'zásilkovna', 'Zasilkovna'], 'Balíkovna' => ['balikovna', 'Balikovna'],
            'Česká pošta' => ['ceska posta', 'Ceska posta', 'česká pošta', 'posta', 'pošta']] as $spravne => $varianty) {
            DB::table('zakazky')->whereIn('dopravce', $varianty)->update(['dopravce' => $spravne]);
        }
    }

    public function down(): void
    {
    }
};
