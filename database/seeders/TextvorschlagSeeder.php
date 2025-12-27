<?php

namespace Database\Seeders;

use App\Models\Textvorschlag;
use Illuminate\Database\Seeder;

class TextvorschlagSeeder extends Seeder
{
    public function run(): void
    {
        $vorschlaege = [
            // =====================================================
            // Reinigung - Bemerkungen
            // =====================================================
            
            // Deutsch
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'de', 'text' => 'Fenster auch gereinigt', 'sortierung' => 1],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'de', 'text' => 'Treppenhaus gereinigt', 'sortierung' => 2],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'de', 'text' => 'Nur Eingang gereinigt', 'sortierung' => 3],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'de', 'text' => 'Niemand zu Hause', 'sortierung' => 4],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'de', 'text' => 'Schluessel nicht vorhanden', 'sortierung' => 5],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'de', 'text' => 'Garage auch gereinigt', 'sortierung' => 6],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'de', 'text' => 'Keller gereinigt', 'sortierung' => 7],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'de', 'text' => 'Balkon gereinigt', 'sortierung' => 8],
            
            // Italienisch
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'it', 'text' => 'Finestre anche pulite', 'sortierung' => 1],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'it', 'text' => 'Scale pulite', 'sortierung' => 2],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'it', 'text' => 'Solo ingresso pulito', 'sortierung' => 3],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'it', 'text' => 'Nessuno a casa', 'sortierung' => 4],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'it', 'text' => 'Chiave non disponibile', 'sortierung' => 5],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'it', 'text' => 'Garage anche pulito', 'sortierung' => 6],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'it', 'text' => 'Cantina pulita', 'sortierung' => 7],
            ['kategorie' => 'reinigung_bemerkung', 'sprache' => 'it', 'text' => 'Balcone pulito', 'sortierung' => 8],

            // =====================================================
            // Reinigung - SMS/WhatsApp Nachrichten
            // Die Vorlagen werden DE+IT kombiniert verwendet!
            // =====================================================
            
            // Deutsch (wird mit IT kombiniert: text_de + "---" + text_it)
            ['kategorie' => 'reinigung_nachricht', 'sprache' => 'de', 'text' => 'Guten Tag, wir kommen am {{DATUM}} zwischen {{VON}} und {{BIS}} Uhr zur Reinigung.', 'sortierung' => 1],
            ['kategorie' => 'reinigung_nachricht', 'sprache' => 'de', 'text' => 'Hallo, die Reinigung wurde durchgeführt. Vielen Dank!', 'sortierung' => 2],
            ['kategorie' => 'reinigung_nachricht', 'sprache' => 'de', 'text' => 'Guten Tag, wir waren heute da, aber niemand hat geöffnet. Bitte um Rückruf.', 'sortierung' => 3],
            ['kategorie' => 'reinigung_nachricht', 'sprache' => 'de', 'text' => 'Hallo, wann passt Ihnen die nächste Reinigung?', 'sortierung' => 4],
            ['kategorie' => 'reinigung_nachricht', 'sprache' => 'de', 'text' => 'Guten Tag, wir müssen den Termin leider verschieben. Wann passt es Ihnen?', 'sortierung' => 5],
            
            // Italienisch (gleiche Reihenfolge!)
            ['kategorie' => 'reinigung_nachricht', 'sprache' => 'it', 'text' => 'Buongiorno, veniamo il {{DATUM}} tra le {{VON}} e le {{BIS}} per la pulizia.', 'sortierung' => 1],
            ['kategorie' => 'reinigung_nachricht', 'sprache' => 'it', 'text' => 'Salve, la pulizia è stata effettuata. Grazie!', 'sortierung' => 2],
            ['kategorie' => 'reinigung_nachricht', 'sprache' => 'it', 'text' => 'Buongiorno, siamo passati oggi ma non ha aperto nessuno. Ci richiami per favore.', 'sortierung' => 3],
            ['kategorie' => 'reinigung_nachricht', 'sprache' => 'it', 'text' => 'Salve, quando Le fa comodo la prossima pulizia?', 'sortierung' => 4],
            ['kategorie' => 'reinigung_nachricht', 'sprache' => 'it', 'text' => 'Buongiorno, dobbiamo spostare l\'appuntamento. Quando Le va bene?', 'sortierung' => 5],

            // =====================================================
            // Angebot - Einleitung
            // =====================================================
            
            ['kategorie' => 'angebot_einleitung', 'sprache' => 'de', 'text' => 'Sehr geehrte Damen und Herren, wir freuen uns, Ihnen folgendes Angebot zu unterbreiten:', 'sortierung' => 1],
            ['kategorie' => 'angebot_einleitung', 'sprache' => 'de', 'text' => 'Wie telefonisch besprochen, erhalten Sie anbei unser Angebot:', 'sortierung' => 2],
            ['kategorie' => 'angebot_einleitung', 'sprache' => 'de', 'text' => 'Vielen Dank fuer Ihre Anfrage. Gerne unterbreiten wir Ihnen folgendes Angebot:', 'sortierung' => 3],
            
            ['kategorie' => 'angebot_einleitung', 'sprache' => 'it', 'text' => 'Gentili Signore e Signori, siamo lieti di presentarVi la seguente offerta:', 'sortierung' => 1],
            ['kategorie' => 'angebot_einleitung', 'sprache' => 'it', 'text' => 'Come discusso telefonicamente, Vi inviamo la nostra offerta:', 'sortierung' => 2],
            ['kategorie' => 'angebot_einleitung', 'sprache' => 'it', 'text' => 'Grazie per la Vostra richiesta. Siamo lieti di presentarVi la seguente offerta:', 'sortierung' => 3],

            // =====================================================
            // Angebot - Bemerkung Kunde
            // =====================================================
            
            ['kategorie' => 'angebot_bemerkung_kunde', 'sprache' => 'de', 'text' => 'Die Preise verstehen sich ohne MwSt.', 'sortierung' => 1],
            ['kategorie' => 'angebot_bemerkung_kunde', 'sprache' => 'de', 'text' => 'Zahlbar innerhalb von 30 Tagen.', 'sortierung' => 2],
            ['kategorie' => 'angebot_bemerkung_kunde', 'sprache' => 'de', 'text' => 'Material und Anfahrt inklusive.', 'sortierung' => 3],
            
            ['kategorie' => 'angebot_bemerkung_kunde', 'sprache' => 'it', 'text' => 'I prezzi si intendono senza IVA.', 'sortierung' => 1],
            ['kategorie' => 'angebot_bemerkung_kunde', 'sprache' => 'it', 'text' => 'Pagamento entro 30 giorni.', 'sortierung' => 2],
            ['kategorie' => 'angebot_bemerkung_kunde', 'sprache' => 'it', 'text' => 'Materiale e trasporto inclusi.', 'sortierung' => 3],
        ];

        foreach ($vorschlaege as $v) {
            Textvorschlag::firstOrCreate(
                [
                    'kategorie' => $v['kategorie'],
                    'sprache' => $v['sprache'],
                    'text' => $v['text'],
                ],
                [
                    'aktiv' => true,
                    'sortierung' => $v['sortierung'],
                ]
            );
        }

        $this->command->info('Textvorschläge erstellt: ' . count($vorschlaege));
    }
}
