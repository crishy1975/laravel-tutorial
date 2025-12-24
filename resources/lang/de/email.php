<?php

// ═══════════════════════════════════════════════════════════════════════════
// resources/lang/de/email.php
// Zweisprachige E-Mail-Texte (DE/IT)
// ═══════════════════════════════════════════════════════════════════════════

return [

    // ═══════════════════════════════════════════════════════════
    // 🧾 RECHNUNG / FATTURA
    // ═══════════════════════════════════════════════════════════
    'rechnung' => [
        'betreff' => 'Fattura :nummer - :name',
        
        'anrede' => "Gentili Signore e Signori,\nSehr geehrte Damen und Herren,",
        
        'text' => "in allegato la nostra fattura n. :nummer.\nAnbei erhalten Sie unsere Rechnung Nr. :nummer.",
        
        'gruss' => "Cordiali saluti\nMit freundlichen Grüßen",
        
        'standard_nachricht' => "Gentili Signore e Signori,\nSehr geehrte Damen und Herren,\n\nin allegato la nostra fattura n. :nummer.\nAnbei erhalten Sie unsere Rechnung Nr. :nummer.\n\nCordiali saluti\nMit freundlichen Grüßen",
    ],

    // ═══════════════════════════════════════════════════════════
    // ⚠️ MAHNUNG / SOLLECITO
    // ═══════════════════════════════════════════════════════════
    'mahnung' => [
        'betreff_1' => 'Zahlungserinnerung / Sollecito di pagamento - Fattura :nummer',
        'betreff_2' => '2. Mahnung / 2° Sollecito - Fattura :nummer',
        'betreff_3' => 'Letzte Mahnung / Ultimo sollecito - Fattura :nummer',
        
        'text_1' => "permetteteci di ricordarVi che la fattura indicata risulta ancora non saldata.\nWir erlauben uns Sie daran zu erinnern, dass die angegebene Rechnung noch offen ist.",
        
        'text_2' => "con riferimento alla nostra precedente comunicazione, Vi ricordiamo che la fattura risulta ancora non pagata.\nBezugnehmend auf unsere vorherige Mitteilung erinnern wir Sie daran, dass die Rechnung noch nicht bezahlt wurde.",
        
        'text_3' => "nonostante i nostri precedenti solleciti, la fattura risulta ancora non saldata. Vi preghiamo di provvedere al pagamento entro 7 giorni.\nTrotz unserer bisherigen Mahnungen ist die Rechnung noch offen. Bitte begleichen Sie den Betrag innerhalb von 7 Tagen.",
        
        'gruss' => "Cordiali saluti\nMit freundlichen Grüßen",
    ],

    // ═══════════════════════════════════════════════════════════
    // 📋 ANGEBOT / OFFERTA
    // ═══════════════════════════════════════════════════════════
    'angebot' => [
        'betreff' => 'Offerta / Angebot :nummer',
        
        'text' => "in allegato la nostra offerta come da Vs. richiesta.\nAnbei erhalten Sie unser Angebot gemäß Ihrer Anfrage.\n\nL'offerta è valida fino al :gueltig_bis.\nDas Angebot ist gültig bis zum :gueltig_bis.",
        
        'gruss' => "Cordiali saluti\nMit freundlichen Grüßen",
    ],

    // ═══════════════════════════════════════════════════════════
    // 📝 ALLGEMEINE TEXTE
    // ═══════════════════════════════════════════════════════════
    'allgemein' => [
        'footer_automatisch' => "Questa e-mail è stata generata automaticamente.\nDiese E-Mail wurde automatisch generiert.",
        
        'anhaenge_hinweis' => "In allegato / Im Anhang:",
        
        'rueckfragen' => "Per qualsiasi domanda siamo a Vostra disposizione.\nFür Rückfragen stehen wir Ihnen gerne zur Verfügung.",
    ],

    // ═══════════════════════════════════════════════════════════
    // 🏷️ LABELS (ZWEISPRACHIG)
    // ═══════════════════════════════════════════════════════════
    'labels' => [
        // Dokumenttypen
        'fattura' => 'Fattura / Rechnung',
        'offerta' => 'Offerta / Angebot',
        'sollecito' => 'Sollecito / Mahnung',
        
        // Rechnungsnummer
        'numero_fattura' => 'Numero fattura',
        'rechnungsnummer' => 'Rechnungsnummer',
        
        // Datum
        'data_fattura' => 'Data fattura',
        'rechnungsdatum' => 'Rechnungsdatum',
        'data_scadenza' => 'Data scadenza',
        'faelligkeitsdatum' => 'Fälligkeitsdatum',
        
        // Beträge
        'imponibile' => 'Imponibile',
        'nettobetrag' => 'Nettobetrag',
        'iva' => 'IVA',
        'mwst' => 'MwSt',
        'totale' => 'Totale',
        'gesamtbetrag' => 'Gesamtbetrag',
        
        // Zahlung
        'modalita_pagamento' => 'Modalità di pagamento',
        'zahlungsinformationen' => 'Zahlungsinformationen',
        'condizioni' => 'Condizioni',
        'bedingungen' => 'Bedingungen',
        
        // Anhänge
        'allegati' => 'Allegati',
        'anhaenge' => 'Anhänge',
        
        // Details
        'dettagli_fattura' => 'Dettagli fattura',
        'rechnungsdetails' => 'Rechnungsdetails',
    ],

];