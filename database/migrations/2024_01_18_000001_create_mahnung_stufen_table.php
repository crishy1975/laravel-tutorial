<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahnung_stufen', function (Blueprint $table) {
            $table->id();
            $table->integer('stufe')->unique();          // 0, 1, 2, 3
            $table->string('name_de', 100);              // "Zahlungserinnerung"
            $table->string('name_it', 100);              // "Sollecito di pagamento"
            $table->integer('tage_ueberfaellig');        // Ab wann diese Stufe greift
            $table->decimal('spesen', 8, 2)->default(0); // Mahnspesen
            $table->text('text_de');                     // Deutscher Mahntext
            $table->text('text_it');                     // Italienischer Mahntext
            $table->string('betreff_de', 255);           // E-Mail Betreff DE
            $table->string('betreff_it', 255);           // E-Mail Betreff IT
            $table->boolean('aktiv')->default(true);
            $table->timestamps();
        });

        // Standard-Mahnstufen einfügen
        $this->seedDefaultStufen();
    }

    public function down(): void
    {
        Schema::dropIfExists('mahnung_stufen');
    }

    private function seedDefaultStufen(): void
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
