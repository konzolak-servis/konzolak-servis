<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zpravy', function (Blueprint $table) {
            $table->id();

            $table->enum('smer', ['in', 'out'])->default('in')->index();
            $table->string('schranka')->nullable()->index();   // info@ / servis@ / admin@
            $table->string('od')->nullable();
            $table->string('od_jmeno')->nullable();
            $table->string('pro')->nullable();
            $table->string('predmet')->nullable();
            $table->longText('telo_text')->nullable();
            $table->longText('telo_html')->nullable();

            $table->string('message_id')->nullable()->index();
            $table->string('in_reply_to')->nullable();
            $table->text('reference')->nullable();

            $table->timestamp('datum')->nullable();            // hlavička Date z e-mailu
            $table->timestamp('precteno_at')->nullable()->index();
            $table->boolean('spam')->default(false);

            $table->json('prilohy')->nullable();               // metadata příloh

            $table->foreignId('zakazka_id')->nullable()->constrained('zakazky')->nullOnDelete();
            $table->foreignId('zakaznik_id')->nullable()->constrained('zakaznici')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zpravy');
    }
};
