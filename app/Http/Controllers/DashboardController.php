<?php

namespace App\Http\Controllers;

use App\Models\GebaeudeLog;
use App\Models\RechnungLog;
use App\Models\Rechnung;
use App\Models\Mahnung;
use App\Models\BankBuchung;
use App\Models\Gebaeude;
use App\Models\Spruch;
use App\Models\Backup;
use App\Services\FaelligkeitsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        protected FaelligkeitsService $faelligkeitsService
    ) {}

    /**
     * Dashboard anzeigen
     */
    public function index()
    {
        $user = Auth::user();
        
        // Begrüßung mit Spruch
        $begruessung = $this->getBegruessung($user->name ?? 'Chef');
        
        // Statistiken
        $stats = $this->getStatistiken();
        
        // Offene Erinnerungen (Gebäude + Rechnungen)
        $gebaeudeErinnerungen = $this->getGebaeudeErinnerungen();
        $rechnungErinnerungen = $this->getRechnungErinnerungen();
        
        // Alle Erinnerungen zusammenführen und sortieren
        $alleErinnerungen = $gebaeudeErinnerungen->concat($rechnungErinnerungen)
            ->sortBy('erinnerung_datum')
            ->take(20);
        
        return view('dashboard.index', compact(
            'begruessung',
            'stats',
            'alleErinnerungen',
            'gebaeudeErinnerungen',
            'rechnungErinnerungen'
        ));
    }

    /**
     * ⭐ NEU: Fälligkeiten aktualisieren (AJAX)
     */
    public function aktualisiereFaelligkeiten(Request $request)
    {
        try {
            $stats = $this->faelligkeitsService->aktualisiereAlle();
            
            return response()->json([
                'ok' => true,
                'message' => sprintf(
                    'Fälligkeiten aktualisiert: %d geprüft, %d fällig, %d geändert',
                    $stats['gesamt'],
                    $stats['faellig'],
                    $stats['geaendert']
                ),
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler bei Fälligkeits-Update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'ok' => false,
                'message' => 'Fehler: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Begrüßung mit zufälligem Spruch aus der Datenbank
     */
    private function getBegruessung(string $name): array
    {
        // Zeitzone für Italien/Deutschland (MEZ/MESZ)
        $now = Carbon::now('Europe/Rome');
        $stunde = $now->hour;
        $istWochenende = $now->isWeekend();
        
        // Kategorie bestimmen
        if ($istWochenende) {
            $kategorie = 'wochenende';
        } elseif ($stunde >= 5 && $stunde < 12) {
            $kategorie = 'morgen';
        } elseif ($stunde >= 12 && $stunde < 17) {
            $kategorie = 'mittag';
        } elseif ($stunde >= 17 && $stunde < 21) {
            $kategorie = 'abend';
        } else {
            $kategorie = 'nacht';
        }
        
        // ⭐ Spruch aus Datenbank laden
        $spruchText = $this->getSpruchText($kategorie, $name);
        
        // Tageszeit-Emoji aus Model-Konstante
        $emoji = Spruch::KATEGORIEN[$kategorie]['emoji'] ?? '💬';
        
        return [
            'spruch' => $spruchText,
            'emoji' => $emoji,
            'kategorie' => $kategorie,
            'datum' => $now->locale('de')->isoFormat('dddd, D. MMMM YYYY'),
            'uhrzeit' => $now->format('H:i'),
        ];
    }

    /**
     * Spruch-Text aus Datenbank holen (mit Fallback)
     */
    private function getSpruchText(string $kategorie, string $name): string
    {
        try {
            // Prüfen ob Tabelle existiert
            if (!Schema::hasTable('sprueche')) {
                return $this->getFallbackSpruch($kategorie, $name);
            }
            
            // Zufälligen aktiven Spruch aus der Kategorie holen
            $spruch = Spruch::zufaellig($kategorie);
            
            if ($spruch) {
                return $spruch->formatiert($name);
            }
            
            // Fallback wenn keine Sprüche in DB
            return $this->getFallbackSpruch($kategorie, $name);
            
        } catch (\Exception $e) {
            Log::warning('Fehler beim Laden des Spruchs aus DB', [
                'kategorie' => $kategorie,
                'error' => $e->getMessage(),
            ]);
            
            return $this->getFallbackSpruch($kategorie, $name);
        }
    }

    /**
     * Fallback-Sprüche falls DB nicht verfügbar
     */
    private function getFallbackSpruch(string $kategorie, string $name): string
    {
        $fallbacks = [
            'morgen' => "Guten Morgen %s! Zeit für die Buchhaltung.",
            'mittag' => "Mahlzeit %s! Weiter geht's!",
            'abend' => "Guten Abend %s! Immer noch fleißig?",
            'nacht' => "Hey %s, Nachtschicht?",
            'wochenende' => "Wochenende %s? Respekt!",
        ];
        
        $text = $fallbacks[$kategorie] ?? "Hallo %s!";
        return sprintf($text, $name);
    }

    /**
     * Statistiken laden
     */
    private function getStatistiken(): array
    {
        // Gebäude mit "Rechnung zu schreiben"
        $rechnungZuSchreiben = 0;
        try {
            $rechnungZuSchreiben = Gebaeude::where('rechnung_schreiben', true)->count();
        } catch (\Exception $e) {
            // Gebaeude-Tabelle existiert evtl. nicht
        }
        
        // Fällige Reinigungen
        $faelligeReinigungen = 0;
        try {
            if (Schema::hasTable('gebaude') || Schema::hasTable('gebaeudes')) {
                $tableName = Schema::hasTable('gebaude') ? 'gebaude' : 'gebaeudes';
                $faelligeReinigungen = Gebaeude::where('naechste_reinigung', '<=', today())->count();
            }
        } catch (\Exception $e) {}
        
        // Überfällige Rechnungen
        $ueberfaelligeRechnungen = 0;
        $offenerBetrag = 0;
        try {
            $ueberfaellige = Rechnung::where('status', 'overdue')->get();
            $ueberfaelligeRechnungen = $ueberfaellige->count();
            $offenerBetrag = $ueberfaellige->sum('zahlbar_betrag');
        } catch (\Exception $e) {}
        
        // Tage seit letzter Mahnung
        $tageSeitMahnung = null;
        try {
            if (Schema::hasTable('mahnungen')) {
                $letzteMahnung = Mahnung::latest('erstellt_am')->first();
                if ($letzteMahnung) {
                    $tageSeitMahnung = Carbon::parse($letzteMahnung->erstellt_am)->diffInDays(today());
                }
            }
        } catch (\Exception $e) {}
        
        // Tage seit letztem Match
        $tageSeitMatch = null;
        $unmatchedBuchungen = 0;
        try {
            if (Schema::hasTable('bank_buchungen')) {
                // Letzter erfolgreicher Match
                $letzterMatch = BankBuchung::whereNotNull('rechnung_id')
                    ->latest('matched_at')
                    ->first();
                if ($letzterMatch && $letzterMatch->matched_at) {
                    $tageSeitMatch = Carbon::parse($letzterMatch->matched_at)->diffInDays(today());
                }
                
                // Ungematchte Buchungen
                $unmatchedBuchungen = BankBuchung::whereNull('rechnung_id')
                    ->where('ist_einnahme', true)
                    ->count();
            }
        } catch (\Exception $e) {}
        
        // Erinnerungen (Gebäude + Rechnungen)
        $offeneGebaeudeErinnerungen = 0;
        $offeneRechnungErinnerungen = 0;
        $heuteFaellig = 0;
        $ueberfaelligeErinnerungen = 0;
        
        try {
            // Gebäude-Erinnerungen
            $offeneGebaeudeErinnerungen = GebaeudeLog::where(function($q) {
                    $q->whereNotNull('erinnerung_datum')
                      ->orWhere('typ', 'erinnerung');
                })
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->count();
                
            // Heute fällig (Gebäude)
            $heuteFaellig = GebaeudeLog::whereDate('erinnerung_datum', today())
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->count();
                
            // Überfällig (Gebäude)
            $ueberfaelligeErinnerungen = GebaeudeLog::where(function($q) {
                    $q->whereDate('erinnerung_datum', '<', today())
                      ->orWhere(function($q2) {
                          $q2->where('typ', 'erinnerung')
                             ->whereDate('created_at', '<', today());
                      });
                })
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->count();
        } catch (\Exception $e) {}
        
        try {
            if (Schema::hasTable('rechnung_logs')) {
                // Rechnungs-Erinnerungen
                $offeneRechnungErinnerungen = RechnungLog::where(function($q) {
                        $q->whereNotNull('erinnerung_datum')
                          ->orWhere('typ', 'erinnerung');
                    })
                    ->where(function($q) {
                        $q->where('erinnerung_erledigt', false)
                          ->orWhereNull('erinnerung_erledigt');
                    })
                    ->count();
                    
                // Heute fällig (Rechnungen)
                $heuteFaellig += RechnungLog::whereDate('erinnerung_datum', today())
                    ->where(function($q) {
                        $q->where('erinnerung_erledigt', false)
                          ->orWhereNull('erinnerung_erledigt');
                    })
                    ->count();
                    
                // Überfällig (Rechnungen)
                $ueberfaelligeErinnerungen += RechnungLog::where(function($q) {
                        $q->whereDate('erinnerung_datum', '<', today())
                          ->orWhere(function($q2) {
                              $q2->where('typ', 'erinnerung')
                                 ->whereDate('created_at', '<', today());
                          });
                    })
                    ->where(function($q) {
                        $q->where('erinnerung_erledigt', false)
                          ->orWhereNull('erinnerung_erledigt');
                    })
                    ->count();
            }
        } catch (\Exception $e) {}
        
        // Backup-Status
        $backupDownloadFaellig = false;
        $tageSeitBackupDownload = null;
        $nichtHeruntergeladeneBackups = 0;
        
        try {
            if (Schema::hasTable('backups')) {
                $tageSeitBackupDownload = Backup::tageSeitDownload();
                $nichtHeruntergeladeneBackups = Backup::nichtHeruntergeladen();
                
                // Fällig wenn: >= 7 Tage seit letztem Download ODER noch nie heruntergeladen aber Backups existieren
                if ($tageSeitBackupDownload !== null && $tageSeitBackupDownload >= 7) {
                    $backupDownloadFaellig = true;
                } elseif ($tageSeitBackupDownload === null && Backup::count() > 0) {
                    $backupDownloadFaellig = true;
                }
            }
        } catch (\Exception $e) {}
        
        return [
            'rechnung_zu_schreiben' => $rechnungZuSchreiben,
            'faellige_reinigungen' => $faelligeReinigungen,
            'ueberfaellige_rechnungen' => $ueberfaelligeRechnungen,
            'offener_betrag' => $offenerBetrag,
            'tage_seit_mahnung' => $tageSeitMahnung,
            'tage_seit_match' => $tageSeitMatch,
            'unmatched_buchungen' => $unmatchedBuchungen,
            'offene_erinnerungen' => $offeneGebaeudeErinnerungen + $offeneRechnungErinnerungen,
            'heute_faellig' => $heuteFaellig,
            'ueberfaellige_erinnerungen' => $ueberfaelligeErinnerungen,
            'backup_download_faellig' => $backupDownloadFaellig,
            'tage_seit_backup_download' => $tageSeitBackupDownload,
            'nicht_heruntergeladene_backups' => $nichtHeruntergeladeneBackups,
        ];
    }

    /**
     * Gebäude-Erinnerungen laden
     */
    private function getGebaeudeErinnerungen()
    {
        try {
            return GebaeudeLog::with('gebaeude')
                ->where(function($q) {
                    $q->whereNotNull('erinnerung_datum')
                      ->orWhere('typ', 'erinnerung');
                })
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->orderByRaw('COALESCE(erinnerung_datum, created_at) ASC')
                ->limit(50)
                ->get()
                ->map(function($log) {
                    $codex = null;
                    $name = 'Gebäude #' . $log->gebaeude_id;
                    
                    if ($log->gebaeude) {
                        $codex = $log->gebaeude->codex;
                        $name = $log->gebaeude->gebaeude_name 
                            ?? $log->gebaeude->strasse 
                            ?? $name;
                    }
                    
                    $datum = $log->erinnerung_datum ?? $log->created_at;
                    
                    return (object)[
                        'id' => $log->id,
                        'typ' => 'gebaeude',
                        'codex' => $codex,
                        'name' => $name,
                        'rechnungsnummer' => null,
                        'titel' => $codex ? "{$codex} - {$name}" : $name,
                        'beschreibung' => $log->beschreibung,
                        'erinnerung_datum' => $datum,
                        'prioritaet' => $log->prioritaet ?? 'normal',
                        'link' => route('gebaeude.edit', $log->gebaeude_id) . '#content-protokoll',
                        'erledigt_route' => route('gebaeude.logs.erledigt', $log->id),
                        'icon' => 'bi-building',
                        'farbe' => 'primary',
                    ];
                });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Rechnungs-Erinnerungen laden
     */
    private function getRechnungErinnerungen()
    {
        try {
            if (!Schema::hasTable('rechnung_logs')) {
                return collect();
            }
            
            $query = RechnungLog::with(['rechnung', 'rechnung.gebaeude'])
                ->where(function($q) {
                    $q->whereNotNull('erinnerung_datum')
                      ->orWhere('typ', 'erinnerung');
                })
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->orderByRaw('COALESCE(erinnerung_datum, created_at) ASC')
                ->limit(50);
            
            $logs = $query->get();
            
            if ($logs->isEmpty()) {
                return collect();
            }
            
            return $logs->map(function($log) {
                $nummer = '#' . $log->rechnung_id;
                if ($log->rechnung) {
                    if (isset($log->rechnung->jahr) && isset($log->rechnung->laufnummer)) {
                        $nummer = $log->rechnung->jahr . '/' . str_pad($log->rechnung->laufnummer, 4, '0', STR_PAD_LEFT);
                    }
                }
                
                $codex = null;
                $gebName = null;
                
                if ($log->rechnung) {
                    $codex = $log->rechnung->geb_codex ?? null;
                    $gebName = $log->rechnung->geb_name ?? null;
                    
                    if (!$codex && $log->rechnung->gebaeude) {
                        $codex = $log->rechnung->gebaeude->codex;
                    }
                    if (!$gebName && $log->rechnung->gebaeude) {
                        $gebName = $log->rechnung->gebaeude->gebaeude_name;
                    }
                }
                
                $datum = $log->erinnerung_datum ?? $log->created_at;
                
                $erledigtRoute = '#';
                try {
                    $erledigtRoute = route('rechnung.logs.erledigt', $log->id);
                } catch (\Exception $e) {}
                
                return (object)[
                    'id' => $log->id,
                    'typ' => 'rechnung',
                    'codex' => $codex,
                    'name' => $gebName,
                    'rechnungsnummer' => $nummer,
                    'titel' => 'RE ' . $nummer,
                    'beschreibung' => $log->beschreibung ?? $log->titel ?? '',
                    'erinnerung_datum' => $datum,
                    'prioritaet' => $log->prioritaet ?? 'normal',
                    'link' => route('rechnung.edit', $log->rechnung_id),
                    'erledigt_route' => $erledigtRoute,
                    'icon' => 'bi-receipt',
                    'farbe' => 'success',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Fehler beim Laden der Rechnungs-Erinnerungen', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Erinnerung als erledigt markieren (AJAX)
     */
    public function erledigtAjax(Request $request)
    {
        $request->validate([
            'typ' => ['required', 'in:gebaeude,rechnung'],
            'id' => ['required', 'integer'],
        ]);
        
        try {
            if ($request->typ === 'gebaeude') {
                $log = GebaeudeLog::findOrFail($request->id);
            } else {
                $log = RechnungLog::findOrFail($request->id);
            }
            
            $log->update(['erinnerung_erledigt' => true]);
            
            return response()->json([
                'ok' => true,
                'message' => 'Erledigt!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Fehler: ' . $e->getMessage(),
            ], 500);
        }
    }
}
