<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sklad_polozky', function (Blueprint $table) {
            $table->string('platforma')->nullable()->after('kategorie');
        });
    }

    public function down(): void
    {
        Schema::table('sklad_polozky', function (Blueprint $table) {
            $table->dropColumn('platforma');
        });
    }
};
