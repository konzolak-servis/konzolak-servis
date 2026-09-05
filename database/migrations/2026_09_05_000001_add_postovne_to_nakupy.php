<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nakupy', function (Blueprint $table) {
            $table->decimal('postovne', 10, 2)->default(0)->after('celkem');
        });
    }

    public function down(): void
    {
        Schema::table('nakupy', function (Blueprint $table) {
            $table->dropColumn('postovne');
        });
    }
};
