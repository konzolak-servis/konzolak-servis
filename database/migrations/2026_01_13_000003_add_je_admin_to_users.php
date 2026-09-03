<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('je_admin')->default(false)->after('osloveni');
        });

        // Původní účet (nejnižší id) je administrátor.
        $prvni = DB::table('users')->orderBy('id')->value('id');
        if ($prvni) {
            DB::table('users')->where('id', $prvni)->update(['je_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('je_admin');
        });
    }
};
