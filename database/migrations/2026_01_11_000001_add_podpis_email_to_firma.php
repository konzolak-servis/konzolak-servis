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
            $table->text('podpis_email')->nullable()->after('web');
        });

        $f = DB::table('firma')->first();
        $nazev = $f->nazev ?? 'Konzolák Zlín';
        $tel = $f->telefon ?? '';
        $web = $f->web ?? '';

        DB::table('firma')->update([
            'podpis_email' => "S pozdravem\n{$nazev}"
                . ($tel ? "\ntel. {$tel}" : '')
                . ($web ? "\n{$web}" : ''),
        ]);
    }

    public function down(): void
    {
        Schema::table('firma', function (Blueprint $table) {
            $table->dropColumn('podpis_email');
        });
    }
};
