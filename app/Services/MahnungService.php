<?php

namespace App\Services;

use App\Models\Mahnung;
use App\Models\MahnungStufe;
use App\Models\MahnungAusschluss;
use App\Models\MahnungRechnungAusschluss;
use App\Models\Rechnung;
use App\Models\RechnungLog;
use App\Enums\RechnungLogTyp;
use App\Models\Adresse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class MahnungService
{
    // Standard Zahlungsfrist in Tagen
    const ZAHLUNGSFRIST_TAGE = 30;

    // ═══════════════════════════════════════════════════════════════════════
    // ÜBERFÄLLIGE RECHNUNGEN ERMITTELN
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Holt alle überfälligen Rechnungen die mahnbar sind
     */
    public function getUeberfaelligeRechnungen(?int $minTage = null): Collection
    {
        $minTage = $minTage ?? 1;

        // Ausgeschlossene Adressen und Rechnungen
        $ausgeschlosseneAdressen = MahnungAusschluss::getAusgeschlosseneIds();
        $ausgeschlosseneRechnungen = MahnungRechnungAusschluss::getAusgeschlosseneIds();

        $heute = now()->startOfDay();

        // ⭐ Auch postadresse laden für E-Mail-Priorität
        return Rechnung::with(['rechnungsempfaenger', 'gebaeude.postadresse'])
            ->where('status', 'sent')  // Nur versendete, nicht bezahlte
            ->whereNotNull('rechnungsdatum')
            ->whereRaw("DATE_ADD(rechnungsdatum, INTERVAL ? DAY) < ?", [
                self::ZAHLUNGSFRIST_TAGE,
                $heute->toDateString()
            ])
            ->whereNotIn('rechnungsempfaenger_id', $ausgeschlosseneAdressen)
            ->whereNotIn('id', $ausgeschlosseneRechnungen)
            ->get()
            ->map(function ($rechnung) use ($heute) {
                $faelligAm = $rechnung->rechnungsdatum->copy()->addDays(self::ZAHLUNGSFRIST_TAGE);
                $tageUeberfaellig = $faelligAm->diffInDays($heute);
                
                $rechnung->faellig_am = $faelligAm;
                $rechnung->tage_ueberfaellig = $tageUeberfaellig;
                $rechnung->naechste_mahnstufe = $this->ermittleNaechsteMahnstufe($rechnung);
                $rechnung->letzte_mahnung = Mahnung::letzteVonRechnung($rechnung->id);
                
                // ⭐ NEU: Info über offenen Entwurf
                $rechnung->hat_offenen_entwurf = Mahnung::hatOffenenEntwurf($rechnung->id);
                $rechnung->offener_entwurf = $rechnung->hat_offenen_entwurf 
                    ? Mahnung::getOffenerEntwurf($rechnung->id) 
                    : null;
                
                // ⭐ E-Mail-Priorität: Postadresse → Rechnungsempfänger
                $postEmail = $rechnung->gebaeude?->postadresse?->email;
                $rechnungEmail = $rechnung->rechnungsempfaenger?->email;
                $rechnung->hat_email = !empty($postEmail) || !empty($rechnungEmail);
                $rechnung->email_adresse = $postEmail ?: $rechnungEmail;
                $rechnung->email_von_postadresse = !empty($postEmail);
                
                return $rechnung;
            })
            ->filter(fn($r) => $r->tage_ueberfaellig >= $minTage)
            // ⭐ GEÄNDERT: Zeige auch Rechnungen mit offenem Entwurf (aber blockiert)
            ->filter(fn($r) => $r->naechste_mahnstufe !== null || $r->hat_offenen_entwurf)
            ->sortByDesc('tage_ueberfaellig');
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
        $faelligAm = $rechnung->rechnungsdatum->copy()->addDays(self::ZAHLUNGSFRIST_TAGE);
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

        $faelligAm = $rechnung->rechnungsdatum->copy()->addDays(self::ZAHLUNGSFRIST_TAGE);
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
     * ⭐ E-Mail wird aus POSTADRESSE des Gebäudes geholt!
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

        // ⭐ E-Mail aus POSTADRESSE holen (nicht Rechnungsempfänger!)
        $email = $postadresse?->email;
        
        // Fallback: Falls Postadresse keine E-Mail hat, Rechnungsempfänger prüfen
        if (empty($email)) {
            $email = $rechnungsempfaenger->email;
        }
        
        if (empty($email)) {
            // Kein E-Mail → für Postversand markieren
            $mahnung->versandart = 'post';
            $mahnung->status = 'entwurf';  // Bleibt Entwurf bis manuell als Post versendet
            $mahnung->save();
            
            return [
                'erfolg'    => false,
                'grund'     => 'Keine E-Mail-Adresse vorhanden - Postversand erforderlich',
                'post_noetig' => true,
            ];
        }

        try {
            // E-Mail senden (ZWEISPRACHIG)
            $betreff = $mahnung->generiereBetreff();
            $text = $mahnung->generiereText();

            // Mahnungs-PDF erstellen (ZWEISPRACHIG)
            $mahnungPdfPfad = $this->erstellePdf($mahnung);
            $mahnung->pdf_pfad = $mahnungPdfPfad;

            // Rechnungs-PDF herunterladen/generieren
            $rechnungPdfPfad = $this->holeRechnungsPdf($rechnung);

            // ⭐ Name für E-Mail: Postadresse oder Rechnungsempfänger
            $emailName = $postadresse?->name ?? $rechnungsempfaenger->name;

            Mail::send([], [], function ($message) use ($email, $emailName, $betreff, $text, $mahnungPdfPfad, $rechnungPdfPfad, $rechnung) {
                $message->to($email, $emailName)
                    ->subject($betreff)
                    ->text($text);
                
                // 1. Mahnungs-PDF anhängen
                if ($mahnungPdfPfad && file_exists(storage_path('app/' . $mahnungPdfPfad))) {
                    $message->attach(storage_path('app/' . $mahnungPdfPfad), [
                        'as' => 'Mahnung_Sollecito.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }

                // 2. Rechnungs-PDF anhängen
                if ($rechnungPdfPfad && file_exists($rechnungPdfPfad)) {
                    $rechnungsNr = $rechnung->volle_rechnungsnummer ?? $rechnung->laufnummer ?? $rechnung->id;
                    $message->attach($rechnungPdfPfad, [
                        'as' => "Rechnung_Fattura_{$rechnungsNr}.pdf",
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            // Als gesendet markieren
            $mahnung->markiereAlsGesendet('email', $email);

            Log::info('Mahnung per E-Mail versendet', [
                'mahnung_id' => $mahnung->id,
                'email'      => $email,
                'mit_rechnung' => !empty($rechnungPdfPfad),
            ]);

            // ⭐ RechnungLog: Mahnung versandt
            RechnungLog::mahnungVersandt(
                $mahnung->rechnung_id,
                $mahnung->mahnstufe,
                'E-Mail an ' . $email
            );

            return [
                'erfolg' => true,
                'email'  => $email,
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
     * Holt das Rechnungs-PDF über HTTP-Anfrage
     */
    protected function holeRechnungsPdf(Rechnung $rechnung): ?string
    {
        try {
            // PDF über URL abrufen: /rechnung/{id}/pdf
            $url = url('/rechnung/' . $rechnung->id . '/pdf');
            
            // HTTP-Client verwenden
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'verify' => false, // Für localhost
                'cookies' => true,
            ]);

            // Session-Cookie für authentifizierte Anfrage
            $sessionName = config('session.cookie', 'laravel_session');
            $jar = \GuzzleHttp\Cookie\CookieJar::fromArray([
                $sessionName => request()->cookie($sessionName),
                'XSRF-TOKEN' => request()->cookie('XSRF-TOKEN'),
            ], parse_url($url, PHP_URL_HOST));

            $response = $client->get($url, [
                'cookies' => $jar,
                'headers' => [
                    'Accept' => 'application/pdf',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $pdfContent = $response->getBody()->getContents();
                
                // Temporär speichern
                $tempDir = storage_path('app/temp');
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }
                
                $tempPfad = $tempDir . '/rechnung_' . $rechnung->id . '_' . time() . '.pdf';
                file_put_contents($tempPfad, $pdfContent);
                
                return $tempPfad;
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Rechnungs-PDF konnte nicht über URL geladen werden', [
                'rechnung_id' => $rechnung->id,
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            
            // Fallback: Versuche direkt mit DomPDF
            return $this->generiereRechnungsPdfFallback($rechnung);
        }
    }

    /**
     * Fallback: Generiert das Rechnungs-PDF direkt mit DomPDF
     */
    protected function generiereRechnungsPdfFallback(Rechnung $rechnung): ?string
    {
        try {
            if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                return null;
            }

            // Lade Rechnung mit Beziehungen
            $rechnung->load(['rechnungsempfaenger', 'gebaeude', 'positionen']);
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('rechnung.pdf', [
                'rechnung' => $rechnung,
            ]);
            
            // Temporär speichern
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $tempPfad = $tempDir . '/rechnung_' . $rechnung->id . '_' . time() . '.pdf';
            $pdf->save($tempPfad);
            
            return $tempPfad;
        } catch (\Exception $e) {
            Log::warning('Rechnungs-PDF Fallback fehlgeschlagen', [
                'rechnung_id' => $rechnung->id,
                'error' => $e->getMessage(),
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

        $data = [
            'mahnung'    => $mahnung,
            'rechnung'   => $rechnung,
            'empfaenger' => $empfaenger,
            'stufe'      => $stufe,
            'text_de'    => $stufe ? $this->ersetzePlatzhalter($stufe->getText('de'), $mahnung) : '',
            'text_it'    => $stufe ? $this->ersetzePlatzhalter($stufe->getText('it'), $mahnung) : '',
            'betreff_de' => $stufe ? $this->ersetzePlatzhalterBetreff($stufe->getBetreff('de'), $mahnung) : '',
            'betreff_it' => $stufe ? $this->ersetzePlatzhalterBetreff($stufe->getBetreff('it'), $mahnung) : '',
            'firma'      => config('app.firma_name', 'Resch GmbH'),
        ];

        $pdf = Pdf::loadView('mahnungen.pdf', $data);
        
        $filename = sprintf(
            'mahnungen/mahnung_%d_%s_%s.pdf',
            $mahnung->id,
            $mahnung->mahnstufe,
            now()->format('Ymd_His')
        );

        // ⭐ Ordner erstellen falls nicht vorhanden
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
    protected function ersetzePlatzhalter(string $text, Mahnung $mahnung): string
    {
        $rechnung = $mahnung->rechnung;
        
        $platzhalter = [
            '{rechnungsnummer}' => $rechnung?->volle_rechnungsnummer ?? $rechnung?->laufnummer ?? '-',
            '{rechnungsdatum}'  => $rechnung?->rechnungsdatum?->format('d.m.Y') ?? '-',
            '{faelligkeitsdatum}' => $rechnung?->faelligkeitsdatum?->format('d.m.Y') ?? '-',
            '{betrag}'          => number_format($mahnung->rechnungsbetrag, 2, ',', '.'),
            '{spesen}'          => number_format($mahnung->spesen, 2, ',', '.'),
            '{gesamtbetrag}'    => number_format($mahnung->gesamtbetrag, 2, ',', '.'),
            '{tage_ueberfaellig}' => $mahnung->tage_ueberfaellig,
            '{firma}'           => config('app.firma_name', 'Resch GmbH'),
            '{kunde}'           => $rechnung?->rechnungsempfaenger?->name ?? '-',
        ];

        return str_replace(array_keys($platzhalter), array_values($platzhalter), $text);
    }

    /**
     * Ersetzt Platzhalter im Betreff
     */
    protected function ersetzePlatzhalterBetreff(string $betreff, Mahnung $mahnung): string
    {
        $rechnung = $mahnung->rechnung;
        
        $platzhalter = [
            '{rechnungsnummer}' => $rechnung?->volle_rechnungsnummer ?? $rechnung?->laufnummer ?? '-',
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
