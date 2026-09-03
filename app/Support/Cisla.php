<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Číselné řady dokladů ve formátu PREFIX-ROK-NNNN (např. FA-2026-0001).
 * Čítač je veden v tabulce `cislovani` po dvojici (typ, rok).
 */
class Cisla
{
    public static function dalsi(string $typ, string $prefix, ?int $rok = null): string
    {
        $rok ??= (int) date('Y');

        $poradi = DB::transaction(function () use ($typ, $rok) {
            $row = DB::table('cislovani')
                ->where('typ', $typ)
                ->where('rok', $rok)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                DB::table('cislovani')->insert([
                    'typ' => $typ,
                    'rok' => $rok,
                    'posledni' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return 1;
            }

            $next = $row->posledni + 1;
            DB::table('cislovani')
                ->where('id', $row->id)
                ->update(['posledni' => $next, 'updated_at' => now()]);

            return $next;
        });

        return sprintf('%s-%d-%04d', $prefix, $rok, $poradi);
    }
}
