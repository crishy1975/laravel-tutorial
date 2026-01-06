<?php
// database/migrations/2025_01_06_000000_create_gebaeude_aenderungsvorschlaege_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gebaeude_aenderungsvorschlaege', function (Blueprint $table) {
            $table->id();
            
            // ═══════════════════════════════════════════════════════════
            // 🏢 GEBÄUDE-REFERENZ
            // ═══════════════════════════════════════════════════════════
            // NULL = Neues Gebäude
            // Wert = Änderung an bestehendem Gebäude
            $table->unsignedBigInteger('gebaeude_id')->nullable()->index();
            $table->foreign('gebaeude_id')
                ->references('id')
                ->on('gebaeude')
                ->onDelete('cascade');
            
            // ═══════════════════════════════════════════════════════════
            // 👤 MITARBEITER (Ersteller)
            // ═══════════════════════════════════════════════════════════
            $table->unsignedBigInteger('user_id')->index();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            // ═══════════════════════════════════════════════════════════
            // 📝 TYP & STATUS
            // ═══════════════════════════════════════════════════════════
            // Typ: 'neu' oder 'aenderung'
            $table->enum('typ', ['neu', 'aenderung'])->default('aenderung');
            
            // Status: 'pending', 'approved', 'rejected'
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            
            // ═══════════════════════════════════════════════════════════
            // 💾 DATEN
            // ═══════════════════════════════════════════════════════════
            // Alte Daten (nur bei Änderungen, JSON)
            $table->json('alte_daten')->nullable();
            
            // Neue/Vorgeschlagene Daten (JSON)
            $table->json('neue_daten');
            
            // Optional: Bemerkung des Mitarbeiters
            $table->text('bemerkung')->nullable();
            
            // ═══════════════════════════════════════════════════════════
            // ✅ BEARBEITUNG (durch Admin)
            // ═══════════════════════════════════════════════════════════
            $table->unsignedBigInteger('bearbeitet_von')->nullable();
            $table->foreign('bearbeitet_von')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            $table->timestamp('bearbeitet_am')->nullable();
            
            // Optional: Ablehnungsgrund
            $table->text('ablehnungsgrund')->nullable();
            
            // ═══════════════════════════════════════════════════════════
            // 📅 TIMESTAMPS & SOFT DELETES
            // ═══════════════════════════════════════════════════════════
            $table->timestamps();
            $table->softDeletes();
            
            // ═══════════════════════════════════════════════════════════
            // 📊 INDIZES
            // ═══════════════════════════════════════════════════════════
            $table->index(['status', 'typ']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gebaeude_aenderungsvorschlaege');
    }
};
