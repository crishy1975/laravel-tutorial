<?php

namespace App\Services;

use App\Models\Mahnung;
use App\Models\MahnungStufe;
use App\Models\MahnungAusschluss;
use App\Models\MahnungRechnungAusschluss;
use App\Models\Rechnung;
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

        return Rechnung::with(['rechnungsempfaenger', 'gebaeude'])
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
                $rechnung->hat_email = !empty($rechnung->rechnungsempfaenger?->email);
                
                return $rechnung;
            })
            ->filter(fn($r) => $r->tage_ueberfaellig >= $minTage)
            ->filter(fn($r) => $r->naechste_mahnstufe !== null)  // Nur wenn mahnbar
            ->sortByDesc('tage_ueberfaellig');
    }

    /**
     * Ermittelt die nächste Mahnstufe für eine Rechnung
     */
    public function ermittleNaechsteMahnstufe(Rechnung $rechnung): ?MahnungStufe
    {
        $faelligAm = $rechnung->rechnungsdatum->copy()->addDays(self::ZAHLUNGSFRIST_TAGE);
        $tageUeberfaellig = $faelligAm->diffInDays(now());

        // Höchste bereits versendete Stufe
        $hoechsteGesendete = Mahnung::hoechsteStufeVonRechnung($rechnung->id);

        // Alle aktiven Stufen
        $stufen = MahnungStufe::getAlleAktiven();

        // Finde passende Stufe die noch nicht gesendet wurde
        foreach ($stufen as $stufe) {
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

        // Rechnungsbetrag (Brutto)
        $rechnungsbetrag = (float) ($rechnung->brutto ?? $rechnung->total_brutto ?? 0);
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
     * Versendet eine Mahnung per E-Mail
     * Inkl. Originalrechnung als Anhang
     */
    public function versendeMahnung(Mahnung $mahnung, string $sprache = 'de'): array
    {
        $rechnung = $mahnung->rechnung;
        $empfaenger = $rechnung?->rechnungsempfaenger;

        if (!$empfaenger) {
            return [
                'erfolg' => false,
                'grund'  => 'Kein Rechnungsempfänger gefunden',
            ];
        }

        $email = $empfaenger->email;
        
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
            // E-Mail senden
            $betreff = $mahnung->generiereBetreff($sprache);
            $text = $mahnung->generiereText($sprache);

            // Mahnungs-PDF erstellen
            $mahnungPdfPfad = $this->erstellePdf($mahnung, $sprache);
            $mahnung->pdf_pfad = $mahnungPdfPfad;

            // Rechnungs-PDF herunterladen/generieren
            $rechnungPdfPfad = $this->holeRechnungsPdf($rechnung);

            Mail::send([], [], function ($message) use ($email, $empfaenger, $betreff, $text, $mahnungPdfPfad, $rechnungPdfPfad, $rechnung) {
                $message->to($email, $empfaenger->name)
                    ->subject($betreff)
                    ->text($text);
                
                // 1. Mahnungs-PDF anhängen
                if ($mahnungPdfPfad && file_exists(storage_path('app/' . $mahnungPdfPfad))) {
                    $message->attach(storage_path('app/' . $mahnungPdfPfad), [
                        'as' => 'Mahnung.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }

                // 2. Rechnungs-PDF anhängen
                if ($rechnungPdfPfad && file_exists($rechnungPdfPfad)) {
                    $rechnungsNr = $rechnung->volle_rechnungsnummer ?? $rechnung->laufnummer ?? $rechnung->id;
                    $message->attach($rechnungPdfPfad, [
                        'as' => "Rechnung_{$rechnungsNr}.pdf",
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
     * Versendet mehrere Mahnungen
     */
    public function versendeMahnungenBatch(array $mahnungIds, string $sprache = 'de'): array
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

            $result = $this->versendeMahnung($mahnung, $sprache);
            
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
     * Erstellt PDF für eine Mahnung
     */
    public function erstellePdf(Mahnung $mahnung, string $sprache = 'de'): string
    {
        $rechnung = $mahnung->rechnung;
        $empfaenger = $rechnung?->rechnungsempfaenger;
        $stufe = $mahnung->stufe;

        $data = [
            'mahnung'    => $mahnung,
            'rechnung'   => $rechnung,
            'empfaenger' => $empfaenger,
            'stufe'      => $stufe,
            'sprache'    => $sprache,
            'text'       => $mahnung->generiereText($sprache),
            'betreff'    => $mahnung->generiereBetreff($sprache),
            'firma'      => config('app.firma_name', 'Resch GmbH'),
        ];

        $pdf = Pdf::loadView('mahnungen.pdf', $data);
        
        $filename = sprintf(
            'mahnungen/mahnung_%d_%s_%s.pdf',
            $mahnung->id,
            $mahnung->mahnstufe,
            now()->format('Ymd_His')
        );

        $pdf->save(storage_path('app/' . $filename));

        return $filename;
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
            'ueberfaellig_betrag' => $ueberfaellige->sum(fn($r) => (float) ($r->brutto ?? 0)),
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
