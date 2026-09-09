<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nové firemní telefonní číslo: 778 796 868 (nahrazuje 773 001 488).
 * Mění se jen tehdy, když je uloženo staré číslo nebo je pole prázdné –
 * ručně upravenou hodnotu nepřepisujeme.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('firma')
            ->where(function ($q) {
                $q->whereNull('telefon')
                    ->orWhereIn('telefon', ['773 001 488', '773001488', '+420 773 001 488', '420773001488']);
            })
            ->update(['telefon' => '778 796 868']);
    }

    public function down(): void
    {
        DB::table('firma')
            ->where('telefon', '778 796 868')
            ->update(['telefon' => '773 001 488']);
    }
};
