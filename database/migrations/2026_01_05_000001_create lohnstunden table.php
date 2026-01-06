<?php

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
        Schema::create('lohnstunden', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('datum');
            $table->string('typ', 10)->default('No'); // No, Üb, F, P, A, C, K, U, S, M, BS, H
            $table->decimal('stunden', 5, 2); // z.B. 8.50 Stunden
            $table->text('notizen')->nullable();
            $table->timestamps();

            // Index für schnelle Abfragen
            $table->index(['user_id', 'datum']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lohnstunden');
    }
};
