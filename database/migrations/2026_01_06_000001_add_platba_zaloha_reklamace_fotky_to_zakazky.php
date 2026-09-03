<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            $table->string('zpusob_uhrady')->nullable()->after('cena_celkem');   // hotove | ucet
            $table->date('zaloha_datum')->nullable()->after('zaloha');
            $table->boolean('zaloha_v_prijmech')->default(false)->after('zaloha_datum');
            $table->foreignId('reklamace_k_id')->nullable()->after('id')
                ->constrained('zakazky')->nullOnDelete();
            $table->json('fotky')->nullable()->after('poznamka');
        });

        Schema::table('penezni_denik', function (Blueprint $table) {
            $table->string('zpusob')->nullable()->after('castka');   // hotove | ucet
        });

        Schema::table('obchody', function (Blueprint $table) {
            $table->string('zpusob_uhrady')->nullable()->after('cena');
        });
    }

    public function down(): void
    {
        Schema::table('zakazky', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reklamace_k_id');
            $table->dropColumn(['zpusob_uhrady', 'zaloha_datum', 'zaloha_v_prijmech', 'fotky']);
        });
        Schema::table('penezni_denik', fn (Blueprint $t) => $t->dropColumn('zpusob'));
        Schema::table('obchody', fn (Blueprint $t) => $t->dropColumn('zpusob_uhrady'));
    }
};
