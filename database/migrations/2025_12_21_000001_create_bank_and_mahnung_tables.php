<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Konsolidierte Migration für Bank-Import & Mahnwesen
 * 
 * WICHTIG: Diese Datei ersetzt folgende Einzelmigrationen:
 * - 2024_01_15_000001_alter_bank_buchungen_match_info_to_text.php (LÖSCHEN)
 * - 2024_01_17_000001_create_bank_matching_config_table.php (LÖSCHEN)
 * - 2024_01_18_000001_create_mahnung_stufen_table.php (LÖSCHEN)
 * - 2024_01_18_000002_create_mahnungen_table.php (LÖSCHEN)
 * - 2025_12_14_083043_create_bank_buchungen_table.php (LÖSCHEN)
 * - 2025_12_20_create_mahnung_einstellungen_table.php (LÖSCHEN)
 * 
 * Nur diese EINE Datei behalten!
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════════════
        // 1. BANK-BUCHUNGEN (Basis-Tabelle)
        // ═══════════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('bank_buchungen')) {
            Schema::create('bank_buchungen', function (Blueprint $table) {
                $table->id();
                
                // Import-Referenz
                $table->string('import_datei')->nullable();
                $table->string('import_hash')->nullable();
                $table->timestamp('import_datum')->nullable();
                
                // Konto-Info
                $table->string('iban', 34)->nullable();
                $table->string('konto_name')->nullable();
                
                // Buchungs-Referenz
                $table->string('ntry_ref')->nullable();
                $table->string('msg_id')->nullable();
                
                // Beträge
                $table->decimal('betrag', 12, 2);
                $table->string('waehrung', 3)->default('EUR');
                $table->enum('typ', ['CRDT', 'DBIT']);
                
                // Daten
                $table->date('buchungsdatum');
                $table->date('valutadatum')->nullable();
                
                // Transaktions-Details
                $table->string('tx_code', 20)->nullable();
                $table->string('tx_issuer', 10)->nullable();
                
                // Auftraggeber/Empfänger
                $table->string('gegenkonto_name')->nullable();
                $table->string('gegenkonto_iban', 34)->nullable();
                $table->text('verwendungszweck')->nullable();
                
                // Rechnungs-Matching
                $table->foreignId('rechnung_id')->nullable()->constrained('rechnungen')->nullOnDelete();
                $table->enum('match_status', ['unmatched', 'matched', 'partial', 'manual', 'ignored'])
                      ->default('unmatched');
                $table->text('match_info')->nullable(); // TEXT statt VARCHAR!
                $table->timestamp('matched_at')->nullable();
                
                // Meta
                $table->text('bemerkung')->nullable();
                $table->timestamps();
                
                // Indizes
                $table->index('buchungsdatum');
                $table->index('typ');
                $table->index('match_status');
                $table->index('import_hash');
                $table->index(['iban', 'buchungsdatum']);
            });
        }

        // ═══════════════════════════════════════════════════════════════════════
        // 2. BANK-IMPORT LOGS
        // ═══════════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('bank_import_logs')) {
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

        // ═══════════════════════════════════════════════════════════════════════
        // 3. BANK-MATCHING CONFIG
        // ═══════════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('bank_matching_config')) {
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

            // Standard-Konfiguration
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

        // ═══════════════════════════════════════════════════════════════════════
        // 4. MAHNUNG-STUFEN (muss vor mahnungen kommen!)
        // ═══════════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('mahnung_stufen')) {
            Schema::create('mahnung_stufen', function (Blueprint $table) {
                $table->id();
                $table->integer('stufe')->unique();
                $table->string('name_de', 100);
                $table->string('name_it', 100);
                $table->integer('tage_ueberfaellig');
                $table->decimal('spesen', 8, 2)->default(0);
                $table->text('text_de');
                $table->text('text_it');
                $table->string('betreff_de', 255);
                $table->string('betreff_it', 255);
                $table->boolean('aktiv')->default(true);
                $table->timestamps();
            });

            // Standard-Mahnstufen
            $this->seedMahnungStufen();
        }

        // ═══════════════════════════════════════════════════════════════════════
        // 5. MAHNUNGEN
        // ═══════════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('mahnungen')) {
            Schema::create('mahnungen', function (Blueprint $table) {
                $table->id();
                
                // Beziehungen
                $table->foreignId('rechnung_id')->constrained('rechnungen')->cascadeOnDelete();
                $table->foreignId('mahnung_stufe_id')->constrained('mahnung_stufen');
                
                // Mahndaten
                $table->integer('mahnstufe');
                $table->date('mahndatum');
                $table->integer('tage_ueberfaellig');
                
                // Beträge
                $table->decimal('rechnungsbetrag', 10, 2);
                $table->decimal('spesen', 8, 2)->default(0);
                $table->decimal('gesamtbetrag', 10, 2);
                
                // Versand
                $table->enum('versandart', ['email', 'post', 'keine'])->default('keine');
                $table->timestamp('email_gesendet_am')->nullable();
                $table->string('email_adresse')->nullable();
                $table->boolean('email_fehler')->default(false);
                $table->text('email_fehler_text')->nullable();
                
                // PDF
                $table->string('pdf_pfad')->nullable();
                
                // Status
                $table->enum('status', ['entwurf', 'gesendet', 'storniert'])->default('entwurf');
                $table->text('bemerkung')->nullable();
                
                $table->timestamps();
                
                // Eine Mahnstufe pro Rechnung
                $table->unique(['rechnung_id', 'mahnstufe']);
            });
        }

        // ═══════════════════════════════════════════════════════════════════════
        // 6. MAHNUNG-AUSSCHLÜSSE (Kunden)
        // ═══════════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('mahnung_ausschluesse')) {
            Schema::create('mahnung_ausschluesse', function (Blueprint $table) {
                $table->id();
                $table->foreignId('adresse_id')->constrained('adressen')->cascadeOnDelete();
                $table->string('grund', 255)->nullable();
                $table->date('bis_datum')->nullable();
                $table->boolean('aktiv')->default(true);
                $table->timestamps();
                
                $table->unique('adresse_id');
            });
        }

        // ═══════════════════════════════════════════════════════════════════════
        // 7. MAHNUNG-RECHNUNG-AUSSCHLÜSSE
        // ═══════════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('mahnung_rechnung_ausschluesse')) {
            Schema::create('mahnung_rechnung_ausschluesse', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rechnung_id')->constrained('rechnungen')->cascadeOnDelete();
                $table->string('grund', 255)->nullable();
                $table->date('bis_datum')->nullable();
                $table->boolean('aktiv')->default(true);
                $table->timestamps();
                
                $table->unique('rechnung_id');
            });
        }

        // ═══════════════════════════════════════════════════════════════════════
        // 8. MAHNUNG-EINSTELLUNGEN
        // ═══════════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('mahnung_einstellungen')) {
            Schema::create('mahnung_einstellungen', function (Blueprint $table) {
                $table->id();
                $table->string('schluessel', 100)->unique();
                $table->string('wert', 255);
                $table->string('beschreibung', 500)->nullable();
                $table->string('typ', 20)->default('integer');
                $table->timestamps();
            });

            // Standard-Einstellungen
            DB::table('mahnung_einstellungen')->insert([
                [
                    'schluessel'   => 'zahlungsfrist_tage',
                    'wert'         => '30',
                    'beschreibung' => 'Tage nach Rechnungsdatum bis zur Fälligkeit',
                    'typ'          => 'integer',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'schluessel'   => 'wartezeit_zwischen_mahnungen',
                    'wert'         => '10',
                    'beschreibung' => 'Mindestabstand in Tagen zwischen zwei Mahnungen',
                    'typ'          => 'integer',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'schluessel'   => 'min_tage_ueberfaellig',
                    'wert'         => '0',
                    'beschreibung' => 'Mindestanzahl Tage überfällig bevor gemahnt wird',
                    'typ'          => 'integer',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mahnung_einstellungen');
        Schema::dropIfExists('mahnung_rechnung_ausschluesse');
        Schema::dropIfExists('mahnung_ausschluesse');
        Schema::dropIfExists('mahnungen');
        Schema::dropIfExists('mahnung_stufen');
        Schema::dropIfExists('bank_matching_config');
        Schema::dropIfExists('bank_import_logs');
        Schema::dropIfExists('bank_buchungen');
    }

    /**
     * Standard-Mahnstufen einfügen
     */
    private function seedMahnungStufen(): void
    {
        $stufen = [
            [
                'stufe' => 0,
                'name_de' => 'Zahlungserinnerung',
                'name_it' => 'Sollecito di pagamento',
                'tage_ueberfaellig' => 7,
                'spesen' => 0.00,
                'betreff_de' => 'Zahlungserinnerung - Rechnung Nr. {rechnungsnummer}',
                'betreff_it' => 'Sollecito di pagamento - Fattura n. {rechnungsnummer}',
                'text_de' => 'Sehr geehrte Damen und Herren,

bei der Überprüfung unserer Buchhaltung haben wir festgestellt, dass die folgende Rechnung noch nicht beglichen wurde:

Rechnung Nr.: {rechnungsnummer}
Rechnungsdatum: {rechnungsdatum}
Fällig am: {faelligkeitsdatum}
Offener Betrag: {betrag} €

Sollte sich Ihre Zahlung mit diesem Schreiben überschnitten haben, betrachten Sie diese Erinnerung bitte als gegenstandslos.

Andernfalls bitten wir Sie, den ausstehenden Betrag innerhalb der nächsten 7 Tage zu überweisen.

Mit freundlichen Grüßen
{firma}',
                'text_it' => 'Gentili Signore e Signori,

verificando la nostra contabilità abbiamo riscontrato che la seguente fattura non è ancora stata saldata:

Fattura n.: {rechnungsnummer}
Data fattura: {rechnungsdatum}
Scadenza: {faelligkeitsdatum}
Importo dovuto: {betrag} €

Qualora il pagamento si fosse incrociato con la presente comunicazione, Vi preghiamo di considerarla nulla.

In caso contrario, Vi preghiamo di effettuare il bonifico entro i prossimi 7 giorni.

Cordiali saluti
{firma}',
            ],
            [
                'stufe' => 1,
                'name_de' => '1. Mahnung',
                'name_it' => '1° Sollecito',
                'tage_ueberfaellig' => 21,
                'spesen' => 10.00,
                'betreff_de' => '1. Mahnung - Rechnung Nr. {rechnungsnummer}',
                'betreff_it' => '1° Sollecito - Fattura n. {rechnungsnummer}',
                'text_de' => 'Sehr geehrte Damen und Herren,

trotz unserer Zahlungserinnerung konnten wir leider noch keinen Zahlungseingang für die folgende Rechnung feststellen:

Rechnung Nr.: {rechnungsnummer}
Rechnungsdatum: {rechnungsdatum}
Fällig am: {faelligkeitsdatum}
Offener Betrag: {betrag} €
Mahnspesen: {spesen} €
Gesamtbetrag: {gesamtbetrag} €

Wir bitten Sie, den Gesamtbetrag innerhalb von 10 Tagen auf unser Konto zu überweisen.

Mit freundlichen Grüßen
{firma}',
                'text_it' => 'Gentili Signore e Signori,

nonostante il nostro sollecito di pagamento, non abbiamo ancora ricevuto il pagamento per la seguente fattura:

Fattura n.: {rechnungsnummer}
Data fattura: {rechnungsdatum}
Scadenza: {faelligkeitsdatum}
Importo dovuto: {betrag} €
Spese di sollecito: {spesen} €
Importo totale: {gesamtbetrag} €

Vi preghiamo di effettuare il bonifico dell\'importo totale entro 10 giorni.

Cordiali saluti
{firma}',
            ],
            [
                'stufe' => 2,
                'name_de' => '2. Mahnung',
                'name_it' => '2° Sollecito',
                'tage_ueberfaellig' => 35,
                'spesen' => 25.00,
                'betreff_de' => '2. Mahnung - Rechnung Nr. {rechnungsnummer} - Dringend',
                'betreff_it' => '2° Sollecito - Fattura n. {rechnungsnummer} - Urgente',
                'text_de' => 'Sehr geehrte Damen und Herren,

leider haben wir auf unsere bisherigen Mahnungen keine Reaktion erhalten. Die folgende Rechnung ist nach wie vor offen:

Rechnung Nr.: {rechnungsnummer}
Rechnungsdatum: {rechnungsdatum}
Fällig seit: {tage_ueberfaellig} Tagen
Offener Betrag: {betrag} €
Mahnspesen: {spesen} €
Gesamtbetrag: {gesamtbetrag} €

Wir fordern Sie hiermit nachdrücklich auf, den Gesamtbetrag innerhalb von 7 Tagen zu begleichen.

Bei Zahlungsschwierigkeiten kontaktieren Sie uns bitte umgehend, um eine Lösung zu finden.

Mit freundlichen Grüßen
{firma}',
                'text_it' => 'Gentili Signore e Signori,

purtroppo non abbiamo ricevuto alcun riscontro ai nostri precedenti solleciti. La seguente fattura risulta ancora non pagata:

Fattura n.: {rechnungsnummer}
Data fattura: {rechnungsdatum}
Scaduta da: {tage_ueberfaellig} giorni
Importo dovuto: {betrag} €
Spese di sollecito: {spesen} €
Importo totale: {gesamtbetrag} €

Vi sollecitiamo a saldare l\'importo totale entro 7 giorni.

In caso di difficoltà di pagamento, Vi preghiamo di contattarci immediatamente per trovare una soluzione.

Cordiali saluti
{firma}',
            ],
            [
                'stufe' => 3,
                'name_de' => 'Letzte Mahnung',
                'name_it' => 'Diffida finale',
                'tage_ueberfaellig' => 49,
                'spesen' => 40.00,
                'betreff_de' => 'Letzte Mahnung vor rechtlichen Schritten - Rechnung Nr. {rechnungsnummer}',
                'betreff_it' => 'Diffida finale prima di azioni legali - Fattura n. {rechnungsnummer}',
                'text_de' => 'Sehr geehrte Damen und Herren,

trotz mehrfacher Mahnungen ist die folgende Forderung weiterhin offen:

Rechnung Nr.: {rechnungsnummer}
Rechnungsdatum: {rechnungsdatum}
Fällig seit: {tage_ueberfaellig} Tagen
Offener Betrag: {betrag} €
Mahnspesen: {spesen} €
Gesamtbetrag: {gesamtbetrag} €

Dies ist unsere letzte außergerichtliche Mahnung.

Sollte der Gesamtbetrag nicht innerhalb von 7 Tagen auf unserem Konto eingehen, sehen wir uns gezwungen, ohne weitere Ankündigung rechtliche Schritte einzuleiten bzw. ein Inkassounternehmen zu beauftragen.

Die dadurch entstehenden zusätzlichen Kosten gehen zu Ihren Lasten.

Mit freundlichen Grüßen
{firma}',
                'text_it' => 'Gentili Signore e Signori,

nonostante i ripetuti solleciti, il seguente credito risulta ancora insoluto:

Fattura n.: {rechnungsnummer}
Data fattura: {rechnungsdatum}
Scaduta da: {tage_ueberfaellig} giorni
Importo dovuto: {betrag} €
Spese di sollecito: {spesen} €
Importo totale: {gesamtbetrag} €

Questa è la nostra ultima diffida stragiudiziale.

Qualora l\'importo totale non dovesse pervenire sul nostro conto entro 7 giorni, ci vedremo costretti ad intraprendere azioni legali e/o ad incaricare un\'agenzia di recupero crediti senza ulteriore preavviso.

Le spese aggiuntive che ne deriverebbero saranno a Vostro carico.

Cordiali saluti
{firma}',
            ],
        ];

        foreach ($stufen as $stufe) {
            DB::table('mahnung_stufen')->insert(array_merge($stufe, [
                'aktiv' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
