<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Falls alte Tabelle existiert, löschen
        Schema::dropIfExists('textvorschlaege');
        
        Schema::create('textvorschlaege', function (Blueprint $table) {
            $table->id();
            $table->string('kategorie', 50);        // z.B. 'reinigung_nachricht', 'angebot_einleitung'
            $table->string('titel', 100)->nullable(); // Kurzer Titel für Dropdown
            $table->text('text');                    // Der eigentliche Text (zweisprachig)
            $table->boolean('aktiv')->default(true);
            $table->timestamps();

            $table->index(['kategorie', 'aktiv']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('textvorschlaege');
    }
};
