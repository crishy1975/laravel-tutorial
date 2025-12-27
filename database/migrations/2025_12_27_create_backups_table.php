<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('dateiname');
            $table->string('pfad');
            $table->unsignedBigInteger('groesse')->default(0); // in Bytes
            $table->enum('status', ['erstellt', 'heruntergeladen', 'fehlgeschlagen'])->default('erstellt');
            $table->timestamp('erstellt_am');
            $table->timestamp('heruntergeladen_am')->nullable();
            $table->text('log')->nullable(); // JSON mit Schritten
            $table->text('fehler')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('erstellt_am');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
