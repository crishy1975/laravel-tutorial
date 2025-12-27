<?php

namespace App\Http\Controllers;

use App\Models\GebaeudeLog;
use App\Models\RechnungLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class ErinnerungenController extends Controller
{
    /**
     * Erinnerungen-Übersicht
     */
    public function index(Request $request)
    {
        // Filter: offen (default), erledigt, alle
        $filter = $request->get('status', 'offen');
        $typ = $request->get('typ'); // gebaeude, rechnung, null = alle
        $suche = $request->get('suche');
        
        // Erinnerungen laden
        $gebaeudeErinnerungen = $this->getGebaeudeErinnerungen($filter, $suche);
        $rechnungErinnerungen = $this->getRechnungErinnerungen($filter, $suche);
        
        // Nach Typ filtern
        if ($typ === 'gebaeude') {
            $alleErinnerungen = $gebaeudeErinnerungen;
        } elseif ($typ === 'rechnung') {
            $alleErinnerungen = $rechnungErinnerungen;
        } else {
            $alleErinnerungen = $gebaeudeErinnerungen->concat($rechnungErinnerungen);
        }
        
        // Sortieren: Überfällige zuerst, dann nach Datum
        $alleErinnerungen = $alleErinnerungen->sortBy(function($item) use ($filter) {
            $datum = Carbon::parse($item->erinnerung_datum);
            
            if ($filter === 'erledigt') {
                // Erledigte: neueste zuerst (absteigend)
                return -$datum->timestamp;
            }
            
            // Offene: überfällige zuerst, dann chronologisch
            $istUeberfaellig = $datum->isPast() && !$datum->isToday();
            $prefix = $istUeberfaellig ? '0' : '1';
            return $prefix . $datum->format('Y-m-d');
        });
        
        // Statistiken
        $stats = [
            'offen' => $this->countErinnerungen('offen'),
            'erledigt' => $this->countErinnerungen('erledigt'),
            'heute' => $this->countHeuteFaellig(),
            'ueberfaellig' => $this->countUeberfaellig(),
        ];
        
        return view('erinnerungen.index', compact(
            'alleErinnerungen',
            'filter',
            'typ',
            'suche',
            'stats'
        ));
    }

    /**
     * Erinnerung als erledigt/offen markieren (Toggle)
     */
    public function toggle(Request $request)
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
            
            // Toggle
            $neuerStatus = !$log->erinnerung_erledigt;
            $log->update(['erinnerung_erledigt' => $neuerStatus]);
            
            return response()->json([
                'ok' => true,
                'erledigt' => $neuerStatus,
                'message' => $neuerStatus ? 'Als erledigt markiert' : 'Wieder geöffnet',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Fehler: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gebäude-Erinnerungen laden
     */
    private function getGebaeudeErinnerungen(string $filter, ?string $suche = null)
    {
        try {
            $query = GebaeudeLog::with('gebaeude')
                ->where(function($q) {
                    $q->whereNotNull('erinnerung_datum')
                      ->orWhere('typ', 'erinnerung');
                });
            
            // Filter anwenden
            if ($filter === 'offen') {
                $query->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                });
            } elseif ($filter === 'erledigt') {
                $query->where('erinnerung_erledigt', true);
            }
            
            // Suche
            if ($suche) {
                $query->where(function($q) use ($suche) {
                    $q->where('beschreibung', 'like', "%{$suche}%")
                      ->orWhereHas('gebaeude', function($q2) use ($suche) {
                          $q2->where('codex', 'like', "%{$suche}%")
                             ->orWhere('gebaeude_name', 'like', "%{$suche}%")
                             ->orWhere('strasse', 'like', "%{$suche}%");
                      });
                });
            }
            
            return $query->orderByRaw('COALESCE(erinnerung_datum, created_at) DESC')
                ->limit(100)
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
                        'erledigt' => (bool) $log->erinnerung_erledigt,
                        'prioritaet' => $log->prioritaet ?? 'normal',
                        'link' => route('gebaeude.edit', $log->gebaeude_id) . '#content-protokoll',
                        'icon' => 'bi-building',
                        'farbe' => 'primary',
                        'created_at' => $log->created_at,
                    ];
                });
        } catch (\Exception $e) {
            Log::error('Fehler beim Laden der Gebäude-Erinnerungen', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Rechnungs-Erinnerungen laden
     */
    private function getRechnungErinnerungen(string $filter, ?string $suche = null)
    {
        try {
            if (!Schema::hasTable('rechnung_logs')) {
                return collect();
            }
            
            $query = RechnungLog::with(['rechnung', 'rechnung.gebaeude'])
                ->where(function($q) {
                    $q->whereNotNull('erinnerung_datum')
                      ->orWhere('typ', 'erinnerung');
                });
            
            // Filter anwenden
            if ($filter === 'offen') {
                $query->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                });
            } elseif ($filter === 'erledigt') {
                $query->where('erinnerung_erledigt', true);
            }
            
            // Suche
            if ($suche) {
                $query->where(function($q) use ($suche) {
                    $q->where('beschreibung', 'like', "%{$suche}%")
                      ->orWhere('titel', 'like', "%{$suche}%")
                      ->orWhereHas('rechnung', function($q2) use ($suche) {
                          $q2->where('geb_codex', 'like', "%{$suche}%")
                             ->orWhere('geb_name', 'like', "%{$suche}%");
                      });
                });
            }
            
            return $query->orderByRaw('COALESCE(erinnerung_datum, created_at) DESC')
                ->limit(100)
                ->get()
                ->map(function($log) {
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
                    
                    return (object)[
                        'id' => $log->id,
                        'typ' => 'rechnung',
                        'codex' => $codex,
                        'name' => $gebName,
                        'rechnungsnummer' => $nummer,
                        'titel' => 'RE ' . $nummer,
                        'beschreibung' => $log->beschreibung ?? $log->titel ?? '',
                        'erinnerung_datum' => $datum,
                        'erledigt' => (bool) $log->erinnerung_erledigt,
                        'prioritaet' => $log->prioritaet ?? 'normal',
                        'link' => route('rechnung.edit', $log->rechnung_id),
                        'icon' => 'bi-receipt',
                        'farbe' => 'success',
                        'created_at' => $log->created_at,
                    ];
                });
        } catch (\Exception $e) {
            Log::error('Fehler beim Laden der Rechnungs-Erinnerungen', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Erinnerungen zählen
     */
    private function countErinnerungen(string $filter): int
    {
        $count = 0;
        
        try {
            $gebQuery = GebaeudeLog::where(function($q) {
                $q->whereNotNull('erinnerung_datum')
                  ->orWhere('typ', 'erinnerung');
            });
            
            if ($filter === 'offen') {
                $gebQuery->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                });
            } elseif ($filter === 'erledigt') {
                $gebQuery->where('erinnerung_erledigt', true);
            }
            
            $count += $gebQuery->count();
        } catch (\Exception $e) {}
        
        try {
            if (Schema::hasTable('rechnung_logs')) {
                $reQuery = RechnungLog::where(function($q) {
                    $q->whereNotNull('erinnerung_datum')
                      ->orWhere('typ', 'erinnerung');
                });
                
                if ($filter === 'offen') {
                    $reQuery->where(function($q) {
                        $q->where('erinnerung_erledigt', false)
                          ->orWhereNull('erinnerung_erledigt');
                    });
                } elseif ($filter === 'erledigt') {
                    $reQuery->where('erinnerung_erledigt', true);
                }
                
                $count += $reQuery->count();
            }
        } catch (\Exception $e) {}
        
        return $count;
    }

    /**
     * Heute fällige Erinnerungen zählen
     */
    private function countHeuteFaellig(): int
    {
        $count = 0;
        
        try {
            $count += GebaeudeLog::whereDate('erinnerung_datum', today())
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->count();
        } catch (\Exception $e) {}
        
        try {
            if (Schema::hasTable('rechnung_logs')) {
                $count += RechnungLog::whereDate('erinnerung_datum', today())
                    ->where(function($q) {
                        $q->where('erinnerung_erledigt', false)
                          ->orWhereNull('erinnerung_erledigt');
                    })
                    ->count();
            }
        } catch (\Exception $e) {}
        
        return $count;
    }

    /**
     * Überfällige Erinnerungen zählen
     */
    private function countUeberfaellig(): int
    {
        $count = 0;
        
        try {
            $count += GebaeudeLog::whereDate('erinnerung_datum', '<', today())
                ->where(function($q) {
                    $q->where('erinnerung_erledigt', false)
                      ->orWhereNull('erinnerung_erledigt');
                })
                ->count();
        } catch (\Exception $e) {}
        
        try {
            if (Schema::hasTable('rechnung_logs')) {
                $count += RechnungLog::whereDate('erinnerung_datum', '<', today())
                    ->where(function($q) {
                        $q->where('erinnerung_erledigt', false)
                          ->orWhereNull('erinnerung_erledigt');
                    })
                    ->count();
            }
        } catch (\Exception $e) {}
        
        return $count;
    }
}
