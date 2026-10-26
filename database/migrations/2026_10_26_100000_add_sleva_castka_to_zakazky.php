<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sleva buď v procentech (sleva_procent), nebo pevnou částkou v Kč (sleva_pevna),
        // s vlastním textem na dokladech (např. „Sleva za výkup").
        Schema::table('zakazky', function (Blueprint $table) {
            $table->string('sleva_typ', 10)->default('procenta')->after('sleva_procent');
            $table->decimal('sleva_pevna', 10, 2)->default(0)->after('sleva_typ');
            $table->string('sleva_popis', 120)->nullable()->after('sleva_pevna');
        });
    }

    public function down(): void
    {
        Schema::table('zakazky', fn (Blueprint $t) => $t->dropColumn(['sleva_typ', 'sleva_pevna', 'sleva_popis']));
    }
};
