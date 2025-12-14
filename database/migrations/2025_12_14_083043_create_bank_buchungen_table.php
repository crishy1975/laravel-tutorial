<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bank-Buchungen aus CBI-XML Import
     */
    public function up(): void
    {
        Schema::create('bank_buchungen', function (Blueprint $table) {
            $table->id();
            
            // Import-Referenz
            $table->string('import_datei')->nullable()->comment('Dateiname des Imports');
            $table->string('import_hash')->nullable()->comment('Hash zur Duplikat-Erkennung');
            $table->timestamp('import_datum')->nullable();
            
            // Konto-Info
            $table->string('iban', 34)->nullable();
            $table->string('konto_name')->nullable();
            
            // Buchungs-Referenz aus Bank
            $table->string('ntry_ref')->nullable()->comment('NtryRef aus XML');
            $table->string('msg_id')->nullable()->comment('MsgId aus XML-Header');
            
            // Betraege
            $table->decimal('betrag', 12, 2);
            $table->string('waehrung', 3)->default('EUR');
            $table->enum('typ', ['CRDT', 'DBIT'])->comment('CRDT=Eingang, DBIT=Ausgang');
            
            // Daten
            $table->date('buchungsdatum');
            $table->date('valutadatum')->nullable();
            
            // Transaktions-Details
            $table->string('tx_code', 20)->nullable()->comment('BkTxCd z.B. 48//26');
            $table->string('tx_issuer', 10)->nullable()->comment('CBI etc.');
            
            // Auftraggeber/Empfaenger
            $table->string('gegenkonto_name')->nullable();
            $table->string('gegenkonto_iban', 34)->nullable();
            $table->text('verwendungszweck')->nullable()->comment('AddtlTxInf');
            
            // Rechnungs-Matching
            $table->foreignId('rechnung_id')->nullable()->constrained('rechnungen')->nullOnDelete();
            $table->enum('match_status', ['unmatched', 'matched', 'partial', 'manual', 'ignored'])
                  ->default('unmatched');
            $table->string('match_info')->nullable()->comment('Extrahierte Rechnungsnummer etc.');
            $table->timestamp('matched_at')->nullable();
            
            // Meta
            $table->text('bemerkung')->nullable();
            $table->timestamps();
            
            // Indizes
            $table->index('buchungsdatum');
            $table->index('typ');
            $table->index('match_status');
            $table->index('rechnung_id');
            $table->index('import_hash');
            $table->index(['iban', 'buchungsdatum']);
        });

        // Import-Log Tabelle
        Schema::create('bank_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('dateiname');
            $table->string('datei_hash')->unique();
            $table->integer('anzahl_buchungen')->default(0);
            $table->integer('anzahl_neu')->default(0);
            $table->integer('anzahl_duplikate')->default(0);
            $table->integer('anzahl_matched')->default(0);
            $table->string('iban', 34)->nullable();
            $table->date('von_datum')->nullable();
            $table->date('bis_datum')->nullable();
            $table->decimal('saldo_anfang', 12, 2)->nullable();
            $table->decimal('saldo_ende', 12, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_buchungen');
        Schema::dropIfExists('bank_import_logs');
    }
};
