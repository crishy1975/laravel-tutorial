<?php

namespace App\Services;

use App\Models\Mahnung;
use App\Models\MahnungStufe;
use App\Models\MahnungAusschluss;
use App\Models\MahnungRechnungAusschluss;
use App\Models\MahnungEinstellung;
use App\Models\Rechnung;
use App\Models\RechnungLog;
use App\Models\Unternehmensprofil;
use App\Enums\RechnungLogTyp;
use App\Models\Adresse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class MahnungService
{
    // ═══════════════════════════════════════════════════════════════════════
    // ÜBERFÄLLIGE RECHNUNGEN ERMITTELN
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ⭐ ÜBERARBEITET: Holt alle überfälligen Rechnungen die JETZT mahnbar sind
     * 
     * Eine Rechnung ist mahnbar wenn:
     * 1. Status = 'sent' (nicht 'paid', nicht 'draft')
     * 2. Überfällig (Zahlungsfrist abgelaufen)
     * 3. Nicht ausgeschlossen (Adresse oder Rechnung)
     * 4. ENTWEDER: noch nie gemahnt
     *    ODER: letzte gesendete Mahnung älter als Wartezeit
     * 5. Kein offener Entwurf vorhanden
     */
    public function getUeberfaelligeRechnungen(): Collection
    {
        $heute = now()->startOfDay();
        
        // Einstellungen aus Datenbank
        $zahlungsfristTage = MahnungEinstellung::getZahlungsfristTage();
        $wartezeitTage = MahnungEinstellung::getWartezeitZwischenMahnungen();
        $minTageUeberfaellig = MahnungEinstellung::getMinTageUeberfaellig();

        // Ausgeschlossene Adressen und Rechnungen
        $ausgeschlosseneAdressen = MahnungAusschluss::getAusgeschlosseneIds();
        $ausgeschlosseneRechnungen = MahnungRechnungAusschluss::getAusgeschlosseneIds();

        // Rechnungen laden: status = 'sent', überfällig, nicht ausgeschlossen
        return Rechnung::with(['rechnungsempfaenger', 'gebaeude.postadresse'])
            ->where('status', 'sent')  // ⭐ Nur 'sent' - 'paid' ist erledigt!
            ->whereNotNull('rechnungsdatum')
            ->whereRaw("DATE_ADD(rechnungsdatum, INTERVAL ? DAY) < ?", [
                $zahlungsfristTage,
                $heute->toDateString()
            ])
            ->whereNotIn('rechnungsempfaenger_id', $ausgeschlosseneAdressen)
            ->whereNotIn('id', $ausgeschlosseneRechnungen)
            ->get()
            ->map(function ($rechnung) use ($heute, $zahlungsfristTage, $wartezeitTage) {
                // Fälligkeit und Überfälligkeit berechnen
                $faelligAm = $rechnung->rechnungsdatum->copy()->addDays($zahlungsfristTage);
                $tageUeberfaellig = $faelligAm->diffInDays($heute);
                
                $rechnung->faellig_am = $faelligAm;
                $rechnung->tage_ueberfaellig = $tageUeberfaellig;
                
                // Letzte GESENDETE Mahnung ermitteln
                $letzteMahnung = Mahnung::where('rechnung_id', $rechnung->id)
                    ->where('status', 'gesendet')
                    ->orderByDesc('mahndatum')
                    ->first();
                
                $rechnung->letzte_mahnung = $letzteMahnung;
                
                // Tage seit letzter Mahnung
                if ($letzteMahnung && $letzteMahnung->mahndatum) {
                    $rechnung->tage_seit_letzter_mahnung = $letzteMahnung->mahndatum->diffInDays($heute);
                } else {
                    $rechnung->tage_seit_letzter_mahnung = null;
                }
                
                // ⭐ KERNLOGIK: Ist diese Rechnung JETZT mahnbar?
                // Fall 1: Noch nie gemahnt → sofort mahnbar
                // Fall 2: Schon gemahnt → nur wenn Wartezeit abgelaufen
                if ($letzteMahnung === null) {
                    $rechnung->ist_mahnbar = true;
                    $rechnung->grund_nicht_mahnbar = null;
                } elseif ($rechnung->tage_seit_letzter_mahnung >= $wartezeitTage) {
                    $rechnung->ist_mahnbar = true;
                    $rechnung->grund_nicht_mahnbar = null;
                } else {
                    $rechnung->ist_mahnbar = false;
                    $verbleibend = $wartezeitTage - $rechnung->tage_seit_letzter_mahnung;
                    $rechnung->grund_nicht_mahnbar = "Wartezeit: noch {$verbleibend} Tag(e)";
                    $rechnung->tage_bis_mahnbar = $verbleibend;
                }
                
                // Offener Entwurf prüfen
                $rechnung->hat_offenen_entwurf = Mahnung::hatOffenenEntwurf($rechnung->id);
                $rechnung->offener_entwurf = $rechnung->hat_offenen_entwurf 
                    ? Mahnung::getOffenerEntwurf($rechnung->id) 
                    : null;
                
                // Wenn offener Entwurf → nicht neu mahnbar (Entwurf muss erst versendet werden)
                if ($rechnung->hat_offenen_entwurf) {
                    $rechnung->ist_mahnbar = false;
                    $rechnung->grund_nicht_mahnbar = 'Offener Entwurf vorhanden';
                }
                
                // Nächste Mahnstufe ermitteln
                $rechnung->naechste_mahnstufe = $this->ermittleNaechsteMahnstufe($rechnung);
                
                // E-Mail-Priorität: Postadresse → Rechnungsempfänger
                $postEmail = $rechnung->gebaeude?->postadresse?->email;
                $rechnungEmail = $rechnung->rechnungsempfaenger?->email;
                $rechnung->hat_email = !empty($postEmail) || !empty($rechnungEmail);
                $rechnung->email_adresse = $postEmail ?: $rechnungEmail;
                $rechnung->email_von_postadresse = !empty($postEmail);
                
                return $rechnung;
            })
            ->filter(fn($r) => $r->tage_ueberfaellig >= $minTageUeberfaellig)
            // ⭐ Nur Rechnungen mit gültiger nächster Stufe ODER offenem Entwurf anzeigen
            ->filter(fn($r) => $r->naechste_mahnstufe !== null || $r->hat_offenen_entwurf)
            ->sortByDesc('tage_ueberfaellig');
    }

    /**
     * ⭐ NEU: Nur die JETZT mahnbaren Rechnungen (für Mahnlauf-Erstellung)
     */
    public function getMahnbareRechnungen(): Collection
    {
        return $this->getUeberfaelligeRechnungen()
            ->filter(fn($r) => $r->ist_mahnbar === true);
    }

    /**
     * ⭐ NEU: Rechnungen in Wartezeit (zur Info)
     */
    public function getRechnungenInWartezeit(): Collection
    {
        return $this->getUeberfaelligeRechnungen()
            ->filter(fn($r) => $r->ist_mahnbar === false && !$r->hat_offenen_entwurf);
    }

    /**
     * ⭐ NEU: Gesperrte Rechnungen abrufen (via MahnungRechnungAusschluss)
     */
    public function getGesperrteRechnungen(): Collection
    {
        // Alle gültigen Ausschlüsse laden
        $ausschluesse = MahnungRechnungAusschluss::with(['rechnung.rechnungsempfaenger', 'rechnung.gebaeude'])
            ->gueltig()
            ->orderByDesc('created_at')
            ->get();
        
        return $ausschluesse;
    }

    /**
     * ⭐ Ermittelt die nächste Mahnstufe für eine Rechnung
     * 
     * WICHTIG: Eine höhere Stufe wird nur zurückgegeben wenn:
     * 1. Kein offener Entwurf existiert (dieser muss erst versendet werden!)
     * 2. Die vorherige Stufe GESENDET wurde (nicht nur als Entwurf erstellt)
     * 3. Genug Tage überfällig für die nächste Stufe
     */
    public function ermittleNaechsteMahnstufe(Rechnung $rechnung): ?MahnungStufe
    {
        $zahlungsfristTage = MahnungEinstellung::getZahlungsfristTage();
        $faelligAm = $rechnung->rechnungsdatum->copy()->addDays($zahlungsfristTage);
        $tageUeberfaellig = $faelligAm->diffInDays(now());

        // ⭐ WICHTIG: Prüfe ob ein offener Entwurf existiert
        // Wenn ja, muss dieser erst versendet werden!
        if (Mahnung::hatOffenenEntwurf($rechnung->id)) {
            Log::debug('Offener Entwurf existiert - keine neue Mahnung möglich', [
                'rechnung_id' => $rechnung->id,
            ]);
            return null;
        }

        // ⭐ Höchste GESENDETE Stufe (nicht nur erstellt!)
        $hoechsteGesendete = Mahnung::hoechsteGesendeteStufeVonRechnung($rechnung->id);

        // Alle aktiven Stufen
        $stufen = MahnungStufe::getAlleAktiven();

        // Finde passende Stufe die noch nicht gesendet wurde
        foreach ($stufen as $stufe) {
            // Stufe muss höher sein als die höchste GESENDETE
            // UND genug Tage überfällig
            if ($stufe->stufe > $hoechsteGesendete && $tageUeberfaellig >= $stufe->tage_ueberfaellig) {
                return $stufe;
            }
        }

        return null; // Keine weitere Mahnstufe verfügbar
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MAHNUNG ERSTELLEN
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Erstellt eine Mahnung für eine Rechnung
     */
    public function erstelleMahnung(Rechnung $rechnung, ?MahnungStufe $stufe = null): ?Mahnung
    {
        // Stufe ermitteln wenn nicht angegeben
        $stufe = $stufe ?? $this->ermittleNaechsteMahnstufe($rechnung);
        
        if (!$stufe) {
            Log::info('Keine passende Mahnstufe gefunden', ['rechnung_id' => $rechnung->id]);
            return null;
        }

        // Prüfen ob diese Stufe schon existiert
        $existiert = Mahnung::where('rechnung_id', $rechnung->id)
            ->where('mahnstufe', $stufe->stufe)
            ->where('status', '!=', 'storniert')
            ->exists();

        if ($existiert) {
            Log::info('Mahnung für diese Stufe existiert bereits', [
                'rechnung_id' => $rechnung->id,
                'stufe'       => $stufe->stufe,
            ]);
            return null;
        }

        $zahlungsfristTage = MahnungEinstellung::getZahlungsfristTage();
        $faelligAm = $rechnung->rechnungsdatum->copy()->addDays($zahlungsfristTage);
        $tageUeberfaellig = $faelligAm->diffInDays(now());

        // ⭐ Rechnungsbetrag (Brutto) - Feld heißt brutto_summe!
        $rechnungsbetrag = (float) ($rechnung->brutto_summe ?? $rechnung->netto_summe ?? 0);
        $spesen = (float) $stufe->spesen;
        $gesamtbetrag = $rechnungsbetrag + $spesen;

        $mahnung = Mahnung::create([
            'rechnung_id'       => $rechnung->id,
            'mahnung_stufe_id'  => $stufe->id,
            'mahnstufe'         => $stufe->stufe,
            'mahndatum'         => now(),
            'tage_ueberfaellig' => $tageUeberfaellig,
            'rechnungsbetrag'   => $rechnungsbetrag,
            'spesen'            => $spesen,
            'gesamtbetrag'      => $gesamtbetrag,
            'status'            => 'entwurf',
        ]);

        Log::info('Mahnung erstellt', [
            'mahnung_id'  => $mahnung->id,
            'rechnung_id' => $rechnung->id,
            'stufe'       => $stufe->stufe,
        ]);

        // ⭐ RechnungLog: Mahnung erstellt
        RechnungLog::mahnungErstellt(
            $rechnung->id,
            $stufe->stufe,
            number_format($gesamtbetrag, 2, ',', '.') . ' €'
        );

        return $mahnung;
    }

    /**
     * Erstellt Mahnungen für alle überfälligen Rechnungen
     */
    public function erstelleMahnungenBatch(array $rechnungIds): array
    {
        $erstellt = [];
        $fehler = [];

        foreach ($rechnungIds as $rechnungId) {
            try {
                $rechnung = Rechnung::find($rechnungId);
                if (!$rechnung) {
                    $fehler[] = ['id' => $rechnungId, 'grund' => 'Rechnung nicht gefunden'];
                    continue;
                }

                $mahnung = $this->erstelleMahnung($rechnung);
                if ($mahnung) {
                    $erstellt[] = $mahnung;
                }
            } catch (\Exception $e) {
                $fehler[] = ['id' => $rechnungId, 'grund' => $e->getMessage()];
                Log::error('Fehler beim Erstellen der Mahnung', [
                    'rechnung_id' => $rechnungId,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        return [
            'erstellt' => $erstellt,
            'fehler'   => $fehler,
            'anzahl'   => count($erstellt),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MAHNUNG VERSENDEN
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Versendet eine Mahnung per E-Mail (ZWEISPRACHIG DE/IT)
     * Inkl. Originalrechnung als Anhang
     * 
     * Verwendet die SMTP-Konfiguration aus dem Unternehmensprofil
     * (gleiche Logik wie RechnungController)
     */
    public function versendeMahnung(Mahnung $mahnung): array
    {
        $rechnung = $mahnung->rechnung;
        $gebaeude = $rechnung?->gebaeude;
        $postadresse = $gebaeude?->postadresse;
        $rechnungsempfaenger = $rechnung?->rechnungsempfaenger;

        if (!$rechnungsempfaenger) {
            return [
                'erfolg' => false,
                'grund'  => 'Kein Rechnungsempfänger gefunden',
            ];
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 1. E-MAIL-ADRESSE ERMITTELN (Postadresse > Rechnungsempfänger)
        // ──────────────────────────────────────────────────────────────────────────
        $email = $postadresse?->email;
        
        if (empty($email)) {
            $email = $rechnungsempfaenger->email;
        }
        
        if (empty($email)) {
            $mahnung->versandart = 'post';
            $mahnung->status = 'entwurf';
            $mahnung->save();
            
            return [
                'erfolg'      => false,
                'grund'       => 'Keine E-Mail-Adresse vorhanden - Postversand erforderlich',
                'post_noetig' => true,
            ];
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 2. UNTERNEHMENSPROFIL LADEN
        // ──────────────────────────────────────────────────────────────────────────
        $profil = Unternehmensprofil::aktiv();

        if (!$profil) {
            Log::error('Kein aktives Unternehmensprofil gefunden');
            return [
                'erfolg' => false,
                'grund'  => 'Kein aktives Unternehmensprofil gefunden!',
            ];
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 3. SMTP-KONFIGURATION PRÜFEN
        // ──────────────────────────────────────────────────────────────────────────
        if (!$profil->hatSmtpKonfiguration()) {
            return [
                'erfolg' => false,
                'grund'  => 'SMTP nicht konfiguriert! Bitte im Unternehmensprofil einrichten.',
            ];
        }

        $smtpHost = $profil->smtp_host;
        $smtpPort = $profil->smtp_port;
        $smtpUser = $profil->smtp_benutzername;
        $smtpPass = $profil->smtp_passwort;
        $smtpEncryption = $profil->smtp_verschluesselung;
        $absenderEmail = $profil->smtp_benutzername;
        $absenderName = $profil->smtp_absender_name ?: $profil->firmenname;
        $mailerName = 'mahnung_smtp';

        // ──────────────────────────────────────────────────────────────────────────
        // 4. DYNAMISCHE MAILER-KONFIGURATION
        // ──────────────────────────────────────────────────────────────────────────
        Config::set("mail.mailers.{$mailerName}", [
            'transport'  => 'smtp',
            'host'       => $smtpHost,
            'port'       => $smtpPort,
            'encryption' => ($smtpEncryption === 'none' || empty($smtpEncryption)) ? null : $smtpEncryption,
            'username'   => $smtpUser,
            'password'   => $smtpPass,
            'timeout'    => 30,
        ]);

        Log::info('Mahnung E-Mail Versand gestartet', [
            'mahnung_id'  => $mahnung->id,
            'rechnung_id' => $rechnung->id,
            'empfaenger'  => $email,
            'smtp_host'   => $smtpHost,
            'absender'    => $absenderEmail,
        ]);

        // ──────────────────────────────────────────────────────────────────────────
        // 5. PDFs ERSTELLEN
        // ──────────────────────────────────────────────────────────────────────────
        try {
            // Mahnungs-PDF erstellen (ZWEISPRACHIG)
            $mahnungPdfPfad = $this->erstellePdf($mahnung);
            $mahnung->pdf_pfad = $mahnungPdfPfad;

            // Rechnungs-PDF herunterladen/generieren
            $rechnungPdfPfad = $this->holeRechnungsPdf($rechnung);

        } catch (\Exception $e) {
            Log::error('PDF-Generierung fehlgeschlagen', [
                'mahnung_id' => $mahnung->id,
                'error'      => $e->getMessage(),
            ]);
            return [
                'erfolg' => false,
                'grund'  => 'PDF konnte nicht generiert werden: ' . $e->getMessage(),
            ];
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 6. DEBUG-MODUS PRÜFEN
        // ──────────────────────────────────────────────────────────────────────────
        $originalEmail = $email;
        $debugMode = config('app.mahnung_debug_mode', false);
        $debugEmail = config('app.mahnung_debug_email');

        if ($debugMode && $debugEmail) {
            $email = $debugEmail;
            Log::warning('MAHNUNG DEBUG-MODUS AKTIV', [
                'original_email' => $originalEmail,
                'umgeleitet_zu'  => $debugEmail,
                'mahnung_id'     => $mahnung->id,
            ]);
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 7. E-MAIL SENDEN
        // ──────────────────────────────────────────────────────────────────────────
        try {
            $betreff = $mahnung->generiereBetreff();
            $text = $mahnung->generiereText();
            $emailName = $postadresse?->name ?? $rechnungsempfaenger->name;

            // Debug: Betreff anpassen
            if ($debugMode && $debugEmail) {
                $betreff = "[TEST] {$betreff} (→ {$originalEmail})";
            }

            Mail::mailer($mailerName)
                ->send([], [], function ($message) use (
                    $email,
                    $emailName,
                    $absenderEmail,
                    $absenderName,
                    $betreff,
                    $text,
                    $mahnungPdfPfad,
                    $rechnungPdfPfad,
                    $rechnung,
                    $debugMode,
                    $originalEmail
                ) {
                    $message->to($email, $emailName)
                        ->from($absenderEmail, $absenderName)
                        ->subject($betreff);
                    
                    // Text (mit Debug-Hinweis wenn aktiv)
                    if ($debugMode) {
                        $message->text("⚠️ DEBUG - Original: {$originalEmail}\n\n" . $text);
                    } else {
                        $message->text($text);
                    }
                    
                    // 1. Mahnungs-PDF anhängen
                    if ($mahnungPdfPfad && file_exists(storage_path('app/' . $mahnungPdfPfad))) {
                        $message->attach(storage_path('app/' . $mahnungPdfPfad), [
                            'as'   => 'Mahnung_Sollecito.pdf',
                            'mime' => 'application/pdf',
                        ]);
                    }

                    // 2. Rechnungs-PDF anhängen
                    if ($rechnungPdfPfad && file_exists($rechnungPdfPfad)) {
                        $rechnungsNr = $rechnung->volle_rechnungsnummer ?? $rechnung->laufnummer ?? $rechnung->id;
                        $message->attach($rechnungPdfPfad, [
                            'as'   => "Rechnung_Fattura_{$rechnungsNr}.pdf",
                            'mime' => 'application/pdf',
                        ]);
                    }
                });

            // Als gesendet markieren (mit Info über Debug)
            $logEmail = $debugMode ? "{$debugEmail} (Test für: {$originalEmail})" : $email;
            $mahnung->markiereAlsGesendet('email', $logEmail);

            Log::info('Mahnung per E-Mail versendet', [
                'mahnung_id'   => $mahnung->id,
                'email'        => $email,
                'debug_mode'   => $debugMode,
                'original'     => $debugMode ? $originalEmail : null,
                'mit_rechnung' => !empty($rechnungPdfPfad),
            ]);

            // RechnungLog: Mahnung versandt
            $logNachricht = $debugMode 
                ? "TEST an {$debugEmail} (für: {$originalEmail})" 
                : "E-Mail an {$email}";
            
            RechnungLog::mahnungVersandt(
                $mahnung->rechnung_id,
                $mahnung->mahnstufe,
                $logNachricht
            );

            return [
                'erfolg'     => true,
                'email'      => $email,
                'debug_mode' => $debugMode,
            ];

        } catch (\Exception $e) {
            $mahnung->markiereEmailFehler($e->getMessage());

            Log::error('E-Mail-Versand fehlgeschlagen', [
                'mahnung_id' => $mahnung->id,
                'error'      => $e->getMessage(),
            ]);

            return [
                'erfolg' => false,
                'grund'  => $e->getMessage(),
            ];
        }
    }

    /**
     * Generiert das Rechnungs-PDF direkt mit DomPDF
     * (gleiche Logik wie RechnungController)
     */
    protected function holeRechnungsPdf(Rechnung $rechnung): ?string
    {
        try {
            // Lade Rechnung mit allen Beziehungen
            $rechnung->load(['rechnungsempfaenger', 'gebaeude', 'positionen', 'fatturaProfile']);
            
            // Unternehmensprofil laden
            $unternehmen = Unternehmensprofil::aktiv();
            
            $pdf = Pdf::loadView('rechnung.pdf', [
                'rechnung'    => $rechnung,
                'unternehmen' => $unternehmen,
            ]);
            
            $pdf->setPaper('A4', 'portrait');
            
            // Temporär speichern
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $tempPfad = $tempDir . '/rechnung_' . $rechnung->id . '_' . time() . '.pdf';
            $pdf->save($tempPfad);
            
            Log::info('Rechnungs-PDF generiert', [
                'rechnung_id' => $rechnung->id,
                'pfad'        => $tempPfad,
            ]);
            
            return $tempPfad;
            
        } catch (\Exception $e) {
            Log::error('Rechnungs-PDF Generierung fehlgeschlagen', [
                'rechnung_id' => $rechnung->id,
                'error'       => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Versendet mehrere Mahnungen (ZWEISPRACHIG)
     */
    public function versendeMahnungenBatch(array $mahnungIds): array
    {
        $erfolg = [];
        $fehler = [];
        $postNoetig = [];

        foreach ($mahnungIds as $mahnungId) {
            $mahnung = Mahnung::find($mahnungId);
            if (!$mahnung) {
                $fehler[] = ['id' => $mahnungId, 'grund' => 'Mahnung nicht gefunden'];
                continue;
            }

            $result = $this->versendeMahnung($mahnung);
            
            if ($result['erfolg']) {
                $erfolg[] = $mahnung;
            } elseif ($result['post_noetig'] ?? false) {
                $postNoetig[] = $mahnung;
            } else {
                $fehler[] = ['id' => $mahnungId, 'grund' => $result['grund']];
            }
        }

        return [
            'erfolg'      => $erfolg,
            'fehler'      => $fehler,
            'post_noetig' => $postNoetig,
            'statistik'   => [
                'gesendet'    => count($erfolg),
                'post_noetig' => count($postNoetig),
                'fehler'      => count($fehler),
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PDF GENERIERUNG
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Erstellt ZWEISPRACHIGES PDF für eine Mahnung (DE + IT)
     */
    public function erstellePdf(Mahnung $mahnung): string
    {
        $rechnung = $mahnung->rechnung;
        $empfaenger = $rechnung?->rechnungsempfaenger;
        $stufe = $mahnung->stufe;

        // ⭐ Unternehmensprofil laden
        $profil = \App\Models\Unternehmensprofil::aktiv();

        $data = [
            'mahnung'    => $mahnung,
            'rechnung'   => $rechnung,
            'empfaenger' => $empfaenger,
            'stufe'      => $stufe,
            'profil'     => $profil,  // ⭐ NEU: Unternehmensprofil
            'text_de'    => $stufe ? $this->ersetzePlatzhalter($stufe->getText('de'), $mahnung, $profil) : '',
            'text_it'    => $stufe ? $this->ersetzePlatzhalter($stufe->getText('it'), $mahnung, $profil) : '',
            'betreff_de' => $stufe ? $this->ersetzePlatzhalterBetreff($stufe->getBetreff('de'), $mahnung) : '',
            'betreff_it' => $stufe ? $this->ersetzePlatzhalterBetreff($stufe->getBetreff('it'), $mahnung) : '',
            // ⭐ Firmendaten aus Profil
            'firma'      => $profil?->firmenname ?? config('app.firma_name', 'Resch GmbH'),
        ];

        $pdf = Pdf::loadView('mahnungen.pdf', $data);
        
        $filename = sprintf(
            'mahnungen/mahnung_%d_%s_%s.pdf',
            $mahnung->id,
            $mahnung->mahnstufe,
            now()->format('Ymd_His')
        );

        // Ordner erstellen falls nicht vorhanden
        $directory = storage_path('app/mahnungen');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdf->save(storage_path('app/' . $filename));

        return $filename;
    }

    /**
     * Ersetzt Platzhalter im Text
     */
    protected function ersetzePlatzhalter(string $text, Mahnung $mahnung, ?\App\Models\Unternehmensprofil $profil = null): string
    {
        $rechnung = $mahnung->rechnung;
        
        $platzhalter = [
            '{rechnungsnummer}' => $mahnung->rechnungsnummer_anzeige,  // ⭐ Robuster Accessor
            '{rechnungsdatum}'  => $rechnung?->rechnungsdatum?->format('d.m.Y') ?? '-',
            '{faelligkeitsdatum}' => $rechnung?->faelligkeitsdatum?->format('d.m.Y') ?? '-',
            '{betrag}'          => number_format($mahnung->rechnungsbetrag, 2, ',', '.'),
            '{spesen}'          => number_format($mahnung->spesen, 2, ',', '.'),
            '{gesamtbetrag}'    => number_format($mahnung->gesamtbetrag, 2, ',', '.'),
            '{tage_ueberfaellig}' => $mahnung->tage_ueberfaellig,
            '{firma}'           => $profil?->firmenname ?? config('app.firma_name', 'Resch GmbH'),
            '{kunde}'           => $rechnung?->rechnungsempfaenger?->name ?? '-',
        ];

        return str_replace(array_keys($platzhalter), array_values($platzhalter), $text);
    }

    /**
     * Ersetzt Platzhalter im Betreff
     */
    protected function ersetzePlatzhalterBetreff(string $betreff, Mahnung $mahnung): string
    {
        $platzhalter = [
            '{rechnungsnummer}' => $mahnung->rechnungsnummer_anzeige,  // ⭐ Robuster Accessor
        ];

        return str_replace(array_keys($platzhalter), array_values($platzhalter), $betreff);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STATISTIKEN
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Dashboard-Statistiken
     */
    public function getStatistiken(): array
    {
        $ueberfaellige = $this->getUeberfaelligeRechnungen();

        return [
            'ueberfaellig_gesamt' => $ueberfaellige->count(),
            // ⭐ brutto_summe statt brutto!
            'ueberfaellig_betrag' => $ueberfaellige->sum(fn($r) => (float) ($r->brutto_summe ?? 0)),
            'ohne_email'          => $ueberfaellige->filter(fn($r) => !$r->hat_email)->count(),
            'mahnungen_entwurf'   => Mahnung::entwurf()->count(),
            'mahnungen_gesendet'  => Mahnung::gesendet()->count(),
            'email_fehler'        => Mahnung::mitEmailFehler()->count(),
            
            // Nach Stufe
            'nach_stufe' => [
                0 => $ueberfaellige->filter(fn($r) => ($r->naechste_mahnstufe?->stufe ?? -1) === 0)->count(),
                1 => $ueberfaellige->filter(fn($r) => ($r->naechste_mahnstufe?->stufe ?? -1) === 1)->count(),
                2 => $ueberfaellige->filter(fn($r) => ($r->naechste_mahnstufe?->stufe ?? -1) === 2)->count(),
                3 => $ueberfaellige->filter(fn($r) => ($r->naechste_mahnstufe?->stufe ?? -1) === 3)->count(),
            ],
        ];
    }

    /**
     * Prüft ob Bank-Buchungen aktuell sind
     */
    public function getBankAktualitaet(): array
    {
        $letzteBuchung = \App\Models\BankBuchung::max('created_at');
        $letzterImport = \App\Models\BankImportLog::max('created_at');

        $tageAlt = $letzteBuchung 
            ? Carbon::parse($letzteBuchung)->diffInDays(now()) 
            : 999;

        return [
            'letzter_import'   => $letzterImport ? Carbon::parse($letzterImport) : null,
            'letzte_buchung'   => $letzteBuchung ? Carbon::parse($letzteBuchung) : null,
            'tage_alt'         => $tageAlt,
            'ist_aktuell'      => $tageAlt <= 3,
            'warnung'          => $tageAlt > 3,
            'warnung_text'     => $tageAlt > 3 
                ? "Bank-Buchungen sind {$tageAlt} Tage alt. Bitte vor dem Mahnlauf aktualisieren!" 
                : null,
        ];
    }
}
