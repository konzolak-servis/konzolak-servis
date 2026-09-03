<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firma', function (Blueprint $table) {
            // Ruční oslovení (5. pád) na nástěnce – nepovinné, jinak se odhadne z jména.
            $table->string('osloveni')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('firma', fn (Blueprint $t) => $t->dropColumn('osloveni'));
    }
};
