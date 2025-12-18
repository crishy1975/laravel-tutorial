<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_matching_config', function (Blueprint $table) {
            $table->id();
            
            // Score-Punkte
            $table->integer('score_iban_match')->default(100);
            $table->integer('score_cig_match')->default(80);
            $table->integer('score_rechnungsnr_match')->default(50);
            $table->integer('score_betrag_exakt')->default(30);
            $table->integer('score_betrag_nah')->default(15);
            $table->integer('score_betrag_abweichung')->default(-40);
            $table->integer('score_name_token_exact')->default(10);
            $table->integer('score_name_token_partial')->default(5);
            
            // Schwellenwerte
            $table->integer('auto_match_threshold')->default(80);
            $table->integer('betrag_abweichung_limit')->default(30);
            
            // Toleranzen
            $table->decimal('betrag_toleranz_exakt', 8, 2)->default(0.10);
            $table->decimal('betrag_toleranz_nah', 8, 2)->default(2.00);
            
            $table->timestamps();
        });

        // Standard-Konfiguration einfügen
        DB::table('bank_matching_config')->insert([
            'score_iban_match'         => 100,
            'score_cig_match'          => 80,
            'score_rechnungsnr_match'  => 50,
            'score_betrag_exakt'       => 30,
            'score_betrag_nah'         => 15,
            'score_betrag_abweichung'  => -40,
            'score_name_token_exact'   => 10,
            'score_name_token_partial' => 5,
            'auto_match_threshold'     => 80,
            'betrag_abweichung_limit'  => 30,
            'betrag_toleranz_exakt'    => 0.10,
            'betrag_toleranz_nah'      => 2.00,
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_matching_config');
    }
};
