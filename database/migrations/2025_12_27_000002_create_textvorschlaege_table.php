<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('textvorschlaege', function (Blueprint $table) {
            $table->id();
            $table->string('kategorie', 50);        // z.B. 'reinigung_bemerkung', 'angebot_einleitung'
            $table->string('sprache', 5)->default('de'); // 'de' oder 'it'
            $table->text('text');
            $table->boolean('aktiv')->default(true);
            $table->integer('sortierung')->default(0);
            $table->timestamps();

            $table->index(['kategorie', 'sprache', 'aktiv']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('textvorschlaege');
    }
};
