<?php

namespace App\Http\Controllers;

use App\Models\GebaeudeLog;
use App\Models\RechnungLog;
use App\Models\Rechnung;
use App\Models\Mahnung;
use App\Models\BankBuchung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Freche Sprüche für die Begrüßung
     */
    private array $sprueche = [
        'morgen' => [
            "Guten Morgen %s! Zeit, die Welt zu erobern... oder zumindest die Buchhaltung.",
            "Moin %s! Der frühe Vogel fängt den Wurm. Du fängst Rechnungen.",
            "Aufgewacht, %s! Die Rechnungen warten nicht von alleine.",
            "Guten Morgen %s! Kaffee ist fertig, Chaos auch.",
            "Hey %s, schon wach? Die Mahnungen vermissen dich!",
            "Buongiorno %s! Heute wird abgerechnet!",
            "Morgen %s! Lass uns Geld verdienen... oder zumindest einfordern.",
            "Guten Morgen %s, du alter Arbeitstier!",
            "Rise and shine, %s! Die Zahlen rufen!",
            "Na %s, ausgeschlafen? Dann mal ran an die Buletten!",
        ],
        'mittag' => [
            "Mahlzeit %s! Schon was geschafft heute?",
            "Hey %s, Mittagspause vorbei? Weiter geht's!",
            "Hallo %s! Die Hälfte ist geschafft... oder auch nicht.",
            "Na %s, schon hungrig? Die Rechnungen sind es auch!",
            "Buon pranzo %s! Nach dem Essen wird weitergearbeitet!",
            "%s, du Held! Halte durch, bald ist Feierabend!",
            "Ciao %s! Noch ein paar Stunden, dann ist Ruhe.",
            "Hey %s, wie läuft's? Chaos oder nur Durcheinander?",
            "Hallo %s! Zeit für den Endspurt!",
            "Na %s, noch motiviert? Fake it till you make it!",
        ],
        'abend' => [
            "Guten Abend %s! Immer noch hier? Respekt!",
            "Hey %s, Überstunden? Die Rechnungen danken es dir!",
            "Buonasera %s! Feierabend ist überbewertet, oder?",
            "Na %s, du Workaholic! Ab nach Hause!",
            "Hallo %s! Die Arbeit läuft nicht weg... leider.",
            "%s, immer noch da? Die Familie vermisst dich!",
            "Abend %s! Morgen ist auch noch ein Tag.",
            "Hey %s, mach Schluss für heute! Du hast es verdient.",
            "Ciao %s! Genug gearbeitet, jetzt wird gelebt!",
            "Guten Abend %s! Zeit für ein Bier... oder zwei.",
        ],
        'nacht' => [
            "Hey %s, kannst du nicht schlafen? Ich auch nicht.",
            "Nachtschicht, %s? Du bist verrückt. Aber sympathisch.",
            "%s um diese Uhrzeit? Hardcore!",
            "Buonanotte %s! Oder doch nicht?",
            "Hey %s, die Geister der unbezahlten Rechnungen grüßen!",
            "Na %s, Insomnia oder Deadline?",
            "Hallo %s, du Nachteule! Schlaf ist was für Schwache.",
            "%s arbeitet nachts? Legend!",
            "Hey %s, die Server schlafen nie. Du anscheinend auch nicht.",
            "Gute Nacht %s! Oh wait, du arbeitest ja noch...",
        ],
        'wochenende' => [
            "Wochenende %s? Was machst du hier?!",
            "Hey %s, selbst am Wochenende? Du brauchst ein Hobby!",
            "%s am Samstag/Sonntag? Respekt... oder Mitleid?",
            "Buon fine settimana %s! Aber offensichtlich nicht für dich.",
            "Hallo %s! Familie? Freunde? Nein? OK, dann arbeite mal.",
            "Hey %s, Wochenende ist zum Erholen da... theoretisch.",
            "%s, du Streber! Wenigstens einer arbeitet hier.",
            "Na %s, die Rechnungen warten nicht aufs Wochenende!",
            "Hey %s, du weißt schon, dass heute frei ist?",
            "Ciao %s! Selbst Workaholics brauchen mal Pause!",
        ],
    ];

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
     * Begrüßung mit zufälligem Spruch
     */
    private function getBegruessung(string $name): array
    {
        $now = Carbon::now();
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
        
        // Zufälligen Spruch aus Kategorie wählen
        $spruchListe = $this->sprueche[$kategorie];
        $spruch = $spruchListe[array_rand($spruchListe)];
        
        // Name einsetzen
        $spruch = sprintf($spruch, $name);
        
        // Tageszeit-Emoji
        $emoji = match($kategorie) {
            'morgen' => '☀️',
            'mittag' => '🌤️',
            'abend' => '🌅',
            'nacht' => '🌙',
            'wochenende' => '🎉',
        };
        
        return [
            'spruch' => $spruch,
            'emoji' => $emoji,
            'kategorie' => $kategorie,
            'datum' => $now->locale('de')->isoFormat('dddd, D. MMMM YYYY'),
            'uhrzeit' => $now->format('H:i'),
        ];
    }

    /**
     * Statistiken laden
     */
    private function getStatistiken(): array
    {
        // Offene Rechnungen (mit Model-Scopes)
        $offeneRechnungen = 0;
        $ueberfaelligeRechnungen = 0;
        $offenerBetrag = 0;
        
        try {
            // Nutze die existierenden Scopes aus dem Rechnung Model
            $offeneRechnungen = Rechnung::offen()->count();
            $ueberfaelligeRechnungen = Rechnung::ueberfaellig()->count();
            $offenerBetrag = Rechnung::unbezahlt()->sum('zahlbar_betrag');
        } catch (\Exception $e) {
            // Fallback falls Scopes nicht existieren
            try {
                $offeneRechnungen = Rechnung::whereIn('status', ['draft', 'sent'])->count();
                $ueberfaelligeRechnungen = Rechnung::where('status', 'sent')
                    ->where('zahlungsziel', '<', now())
                    ->count();
                $offenerBetrag = Rechnung::whereIn('status', ['draft', 'sent'])
                    ->sum('zahlbar_betrag');
            } catch (\Exception $e2) {
                // Tabelle existiert evtl. nicht
            }
        }
        
        // Tage seit letztem Mahnlauf
        $tageSeitMahnung = null;
        try {
            $letzteMahnung = Mahnung::orderByDesc('created_at')->first();
            $tageSeitMahnung = $letzteMahnung 
                ? (int) $letzteMahnung->created_at->diffInDays(now()) 
                : null;
        } catch (\Exception $e) {
            // Mahnung-Tabelle existiert evtl. nicht
        }
        
        // Tage seit letztem Buchungsmatch
        $tageSeitMatch = null;
        $unmatchedBuchungen = 0;
        try {
            $letzterMatch = BankBuchung::whereNotNull('rechnung_id')
                ->orderByDesc('updated_at')
                ->first();
            $tageSeitMatch = $letzterMatch 
                ? (int) $letzterMatch->updated_at->diffInDays(now())
                : null;
            
            // Ungematchte Buchungen
            $unmatchedBuchungen = BankBuchung::whereNull('rechnung_id')
                ->where('ignoriert', false)
                ->where('betrag', '>', 0)
                ->count();
        } catch (\Exception $e) {
            // BankBuchung-Tabelle existiert evtl. nicht
        }
        
        // Offene Erinnerungen (Gebäude)
        $offeneGebaeudeErinnerungen = 0;
        try {
            $offeneGebaeudeErinnerungen = GebaeudeLog::whereNotNull('erinnerung_datum')
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->count();
        } catch (\Exception $e) {
            // GebaeudeLog-Tabelle existiert evtl. nicht
        }
        
        // Offene Erinnerungen (Rechnungen)
        $offeneRechnungErinnerungen = 0;
        try {
            $offeneRechnungErinnerungen = RechnungLog::whereNotNull('erinnerung_datum')
                ->where('erinnerung_erledigt', false)
                ->count();
        } catch (\Exception $e) {
            // RechnungLog-Tabelle existiert evtl. nicht
        }
        
        // Heute fällige Erinnerungen
        $heuteFaellig = 0;
        try {
            $heuteFaellig = GebaeudeLog::whereNotNull('erinnerung_datum')
                ->whereDate('erinnerung_datum', today())
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->count();
        } catch (\Exception $e) {}
        
        try {
            $heuteFaellig += RechnungLog::whereNotNull('erinnerung_datum')
                ->whereDate('erinnerung_datum', today())
                ->where('erinnerung_erledigt', false)
                ->count();
        } catch (\Exception $e) {}
        
        // Überfällige Erinnerungen
        $ueberfaelligeErinnerungen = 0;
        try {
            $ueberfaelligeErinnerungen = GebaeudeLog::whereNotNull('erinnerung_datum')
                ->where('erinnerung_datum', '<', today())
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->count();
        } catch (\Exception $e) {}
        
        try {
            $ueberfaelligeErinnerungen += RechnungLog::whereNotNull('erinnerung_datum')
                ->where('erinnerung_datum', '<', today())
                ->where('erinnerung_erledigt', false)
                ->count();
        } catch (\Exception $e) {}
        
        return [
            'offene_rechnungen' => $offeneRechnungen,
            'ueberfaellige_rechnungen' => $ueberfaelligeRechnungen,
            'offener_betrag' => $offenerBetrag,
            'tage_seit_mahnung' => $tageSeitMahnung,
            'tage_seit_match' => $tageSeitMatch,
            'unmatched_buchungen' => $unmatchedBuchungen,
            'offene_erinnerungen' => $offeneGebaeudeErinnerungen + $offeneRechnungErinnerungen,
            'heute_faellig' => $heuteFaellig,
            'ueberfaellige_erinnerungen' => $ueberfaelligeErinnerungen,
        ];
    }

    /**
     * Gebäude-Erinnerungen laden
     */
    private function getGebaeudeErinnerungen()
    {
        try {
            return GebaeudeLog::with('gebaeude')
                ->whereNotNull('erinnerung_datum')
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->orderBy('erinnerung_datum')
                ->limit(50)
                ->get()
                ->map(function($log) {
                    $titel = 'Gebäude #' . $log->gebaeude_id;
                    if ($log->gebaeude) {
                        $titel = $log->gebaeude->codex 
                            ?? $log->gebaeude->gebaeude_name 
                            ?? $log->gebaeude->strasse 
                            ?? $titel;
                    }
                    
                    return (object)[
                        'id' => $log->id,
                        'typ' => 'gebaeude',
                        'titel' => $titel,
                        'beschreibung' => $log->beschreibung,
                        'erinnerung_datum' => $log->erinnerung_datum,
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
            // Debug: Prüfe ob Tabelle existiert
            if (!Schema::hasTable('rechnung_logs')) {
                Log::warning('Dashboard: Tabelle rechnung_logs existiert nicht');
                return collect();
            }
            
            $query = RechnungLog::with('rechnung')
                ->whereNotNull('erinnerung_datum')
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->orderBy('erinnerung_datum')
                ->limit(50);
            
            // Debug: Zeige SQL
            Log::info('Dashboard Rechnungs-Erinnerungen Query', [
                'sql' => $query->toSql(),
                'count' => $query->count()
            ]);
            
            $logs = $query->get();
            
            if ($logs->isEmpty()) {
                Log::info('Dashboard: Keine Rechnungs-Erinnerungen gefunden');
                return collect();
            }
            
            return $logs->map(function($log) {
                // Rechnungsnummer zusammenbauen
                $nummer = '#' . $log->rechnung_id;
                if ($log->rechnung) {
                    if (isset($log->rechnung->jahr) && isset($log->rechnung->laufnummer)) {
                        $nummer = $log->rechnung->jahr . '/' . str_pad($log->rechnung->laufnummer, 4, '0', STR_PAD_LEFT);
                    }
                }
                
                // Route prüfen - Fallback falls nicht existiert
                $erledigtRoute = '#';
                try {
                    $erledigtRoute = route('rechnung.logs.erledigt', $log->id);
                } catch (\Exception $e) {
                    Log::warning('Route rechnung.logs.erledigt existiert nicht');
                }
                
                return (object)[
                    'id' => $log->id,
                    'typ' => 'rechnung',
                    'titel' => 'Rechnung ' . $nummer,
                    'beschreibung' => $log->beschreibung ?? $log->titel ?? '',
                    'erinnerung_datum' => $log->erinnerung_datum,
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
                'trace' => $e->getTraceAsString()
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
