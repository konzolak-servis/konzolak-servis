<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firma', function (Blueprint $table) {
            $table->text('email_vyzvednuti')->nullable()->after('podpis_email');
        });

        DB::table('firma')->update([
            'email_vyzvednuti' => "Zavolejte prosím vždy předem – ať máme zařízení připravené a jsme na místě.\n"
                . "\n"
                . "Tržnice (budova pod obchodním domem): 9:00–13:00\n"
                . "Jižní Svahy, Na Honech I 4905 (u konečné trolejbusů): 9:00–10:00, 1. patro, zvonek Gřeškovi",
        ]);
    }

    public function down(): void
    {
        Schema::table('firma', function (Blueprint $table) {
            $table->dropColumn('email_vyzvednuti');
        });
    }
};
