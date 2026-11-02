<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Jednorázové srovnání: zakázky v práci (přijato / diagnostika), ke kterým je
     * objednaný díl, přepnout na „čeká na díl" (dřív to objednávka nedělala).
     */
    public function up(): void
    {
        $objednavky = DB::table('objednavky_dilu')
            ->join('zakazky', 'zakazky.id', '=', 'objednavky_dilu.zakazka_id')
            ->where('objednavky_dilu.stav', 'objednano')
            ->whereIn('zakazky.stav', ['prijato', 'diagnostika'])
            ->orderBy('objednavky_dilu.id')
            ->get(['objednavky_dilu.*', 'zakazky.dil_info as zakazka_dil_info']);

        foreach ($objednavky->groupBy('zakazka_id') as $zakazkaId => $skupina) {
            $info = (string) $skupina->first()->zakazka_dil_info;

            foreach ($skupina as $o) {
                $datum = $o->datum_objednavky ? date('d.m.Y', strtotime($o->datum_objednavky)) : '';
                $info = trim(($info !== '' ? $info . "\n" : '')
                    . 'Díl „' . $o->nazev_dilu . '" objednán ' . $datum
                    . ($o->dodavatel ? ' (' . $o->dodavatel . ')' : '') . ', ' . $o->cislo . '.');
            }

            DB::table('zakazky')->where('id', $zakazkaId)->update([
                'stav' => 'ceka_na_dil',
                'dil_objednany' => true,
                'dil_info' => mb_strlen($info) > 255 ? mb_substr($info, -255) : $info,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // jednorázová úprava dat – nevrací se
    }
};
