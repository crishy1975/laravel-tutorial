<?php
// app/Livewire/Mitarbeiter/GebaeudeBearbeiten.php

namespace App\Livewire\Mitarbeiter;

use App\Models\Gebaeude;
use App\Models\GebaeudeAenderungsvorschlag;
use App\Models\Tour;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class GebaeudeBearbeiten extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ═══════════════════════════════════════════════════════════
    // 🔍 SUCHE & FILTER
    // ═══════════════════════════════════════════════════════════
    
    public $suchbegriff = '';
    public $filterTour = '';
    
    // ═══════════════════════════════════════════════════════════
    // 📝 BEARBEITUNGS-MODUS
    // ═══════════════════════════════════════════════════════════
    
    public $gebaeudeId = null;
    public $gebaeude = null;
    public $showModal = false;
    
    // Formular-Felder
    public $codex = '';
    public $gebaeude_name = '';
    public $strasse = '';
    public $hausnummer = '';
    public $plz = '';
    public $wohnort = '';
    public $land = 'IT';
    public $telefon = '';
    public $handy = '';
    public $email = '';
    public $geplante_reinigungen = 0;
    public $bemerkung = '';
    public $bemerkung_mitarbeiter = '';
    
    // Monate
    public $m01 = false;
    public $m02 = false;
    public $m03 = false;
    public $m04 = false;
    public $m05 = false;
    public $m06 = false;
    public $m07 = false;
    public $m08 = false;
    public $m09 = false;
    public $m10 = false;
    public $m11 = false;
    public $m12 = false;
    
    // Touren
    public $selectedTouren = [];

    // ═══════════════════════════════════════════════════════════
    // 🎯 VALIDATION RULES
    // ═══════════════════════════════════════════════════════════
    
    protected function rules()
    {
        return [
            'codex' => 'required|string|max:50|unique:gebaeude,codex,' . $this->gebaeudeId,
            'gebaeude_name' => 'nullable|string|max:255',
            'strasse' => 'required|string|max:255',
            'hausnummer' => 'required|string|max:20',
            'plz' => 'required|string|max:10',
            'wohnort' => 'required|string|max:100',
            'land' => 'required|string|max:2',
            'telefon' => 'nullable|string|max:50',
            'handy' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'geplante_reinigungen' => 'nullable|integer|min:0|max:365',
            'bemerkung' => 'nullable|string|max:1000',
            'bemerkung_mitarbeiter' => 'nullable|string|max:1000',
            'selectedTouren' => 'nullable|array',
        ];
    }

    protected $messages = [
        'codex.required' => 'Codex ist erforderlich',
        'codex.unique' => 'Dieser Codex existiert bereits',
        'strasse.required' => 'Straße ist erforderlich',
        'hausnummer.required' => 'Hausnummer ist erforderlich',
        'plz.required' => 'PLZ ist erforderlich',
        'wohnort.required' => 'Wohnort ist erforderlich',
        'email.email' => 'Bitte gültige E-Mail-Adresse eingeben',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🎬 LIFECYCLE
    // ═══════════════════════════════════════════════════════════
    
    public function render()
    {
        // Modal offen? Keine schwere Berechnung!
        if ($this->showModal) {
            return view('livewire.mitarbeiter.gebaeude-bearbeiten', [
                'gebaeudeListe' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'touren' => Tour::where('aktiv', true)->orderBy('name')->get(),
                'monate' => $this->getMonateArray(),
                'skipList' => true,
            ])->layout('layouts.mitarbeiter');
        }

        // Gebäude-Liste mit Suche und Filter
        $query = Gebaeude::query()->with(['touren']);

        // Suchbegriff
        if (!empty($this->suchbegriff)) {
            $query->where(function($q) {
                $q->where('codex', 'LIKE', '%' . $this->suchbegriff . '%')
                  ->orWhere('gebaeude_name', 'LIKE', '%' . $this->suchbegriff . '%')
                  ->orWhere('strasse', 'LIKE', '%' . $this->suchbegriff . '%')
                  ->orWhere('wohnort', 'LIKE', '%' . $this->suchbegriff . '%');
            });
        }

        // Tour-Filter
        if (!empty($this->filterTour)) {
            $query->whereHas('touren', function($q) {
                $q->where('tour.id', $this->filterTour);
            });
        }

        // Sortierung
        $query->orderBy('strasse')
              ->orderByRaw('CAST(hausnummer AS UNSIGNED)')
              ->orderBy('hausnummer');

        $gebaeude = $query->paginate(20);

        // Ausstehende Änderungen für jedes Gebäude prüfen
        $gebaeudeIds = $gebaeude->pluck('id')->toArray();
        $ausstehendeIds = GebaeudeAenderungsvorschlag::whereIn('gebaeude_id', $gebaeudeIds)
            ->where('status', 'pending')
            ->pluck('gebaeude_id')
            ->unique()
            ->toArray();

        foreach ($gebaeude as $geb) {
            $geb->hat_ausstehende = in_array($geb->id, $ausstehendeIds);
        }

        // Touren für Filter-Dropdown
        $touren = Tour::where('aktiv', true)
            ->orderBy('name')
            ->get();

        return view('livewire.mitarbeiter.gebaeude-bearbeiten', [
            'gebaeudeListe' => $gebaeude,
            'touren' => $touren,
            'monate' => $this->getMonateArray(),
            'skipList' => false,
        ])->layout('layouts.mitarbeiter');
    }

    // ═══════════════════════════════════════════════════════════
    // 🔍 SUCHE & FILTER
    // ═══════════════════════════════════════════════════════════
    
    public function updatingSuchbegriff()
    {
        $this->resetPage();
    }

    public function updatingFilterTour()
    {
        $this->resetPage();
    }

    public function filterZuruecksetzen()
    {
        $this->reset(['suchbegriff', 'filterTour']);
        $this->resetPage();
    }

    // ═══════════════════════════════════════════════════════════
    // 📝 BEARBEITEN
    // ═══════════════════════════════════════════════════════════
    
    public function bearbeiten(int $id)
    {
        $this->gebaeude = Gebaeude::with('touren')->findOrFail($id);
        $this->gebaeudeId = $id;

        // Formular mit Gebäude-Daten füllen
        $this->codex = $this->gebaeude->codex ?? '';
        $this->gebaeude_name = $this->gebaeude->gebaeude_name ?? '';
        $this->strasse = $this->gebaeude->strasse ?? '';
        $this->hausnummer = $this->gebaeude->hausnummer ?? '';
        $this->plz = $this->gebaeude->plz ?? '';
        $this->wohnort = $this->gebaeude->wohnort ?? '';
        $this->land = $this->gebaeude->land ?? 'IT';
        $this->telefon = $this->gebaeude->telefon ?? '';
        $this->handy = $this->gebaeude->handy ?? '';
        $this->email = $this->gebaeude->email ?? '';
        $this->geplante_reinigungen = $this->gebaeude->geplante_reinigungen ?? 0;
        $this->bemerkung = $this->gebaeude->bemerkung ?? '';
        
        // Monate
        $this->m01 = (bool) $this->gebaeude->m01;
        $this->m02 = (bool) $this->gebaeude->m02;
        $this->m03 = (bool) $this->gebaeude->m03;
        $this->m04 = (bool) $this->gebaeude->m04;
        $this->m05 = (bool) $this->gebaeude->m05;
        $this->m06 = (bool) $this->gebaeude->m06;
        $this->m07 = (bool) $this->gebaeude->m07;
        $this->m08 = (bool) $this->gebaeude->m08;
        $this->m09 = (bool) $this->gebaeude->m09;
        $this->m10 = (bool) $this->gebaeude->m10;
        $this->m11 = (bool) $this->gebaeude->m11;
        $this->m12 = (bool) $this->gebaeude->m12;

        // Touren
        $this->selectedTouren = $this->gebaeude->touren->pluck('id')->toArray();

        // Bemerkung zurücksetzen
        $this->bemerkung_mitarbeiter = '';

        // Modal öffnen
        $this->showModal = true;
    }

    public function modalSchliessen()
    {
        $this->showModal = false;
        $this->reset([
            'gebaeudeId', 'gebaeude', 'codex', 'gebaeude_name', 
            'strasse', 'hausnummer', 'plz', 'wohnort', 'land',
            'telefon', 'handy', 'email', 'geplante_reinigungen',
            'bemerkung', 'bemerkung_mitarbeiter', 'selectedTouren',
            'm01', 'm02', 'm03', 'm04', 'm05', 'm06',
            'm07', 'm08', 'm09', 'm10', 'm11', 'm12'
        ]);
        $this->land = 'IT';
    }

    // DEBUG: Einfache Test-Methode
    public function testSpeichern()
    {
        \Log::info('TEST: testSpeichern wurde aufgerufen!');
        session()->flash('success', 'TEST ERFOLGREICH: Die Methode wurde aufgerufen!');
    }

    // ═══════════════════════════════════════════════════════════
    // 💾 SPEICHERN (als Änderungsvorschlag)
    // ═══════════════════════════════════════════════════════════
    
    public function aenderungVorschlagen()
    {
        // DEBUG: Wird diese Methode überhaupt aufgerufen?
        \Log::info('=== aenderungVorschlagen START ===');
        \Log::info('gebaeudeId: ' . $this->gebaeudeId);
        \Log::info('user_id: ' . auth()->id());
        
        // Sofort Flash-Message setzen um zu sehen ob Methode aufgerufen wird
        session()->flash('info', 'Methode wurde aufgerufen für Gebäude ID: ' . $this->gebaeudeId);

        // Debug: Validierung
        try {
            $this->validate();
            \Log::info('Validierung OK');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validierung fehlgeschlagen: ' . json_encode($e->errors()));
            $this->addError('general', 'Validierung fehlgeschlagen: ' . implode(', ', array_map(fn($arr) => implode(', ', $arr), $e->errors())));
            return;
        }

        try {
            $gebaeude = Gebaeude::with('touren')->findOrFail($this->gebaeudeId);
            \Log::info('Gebäude gefunden: ' . $gebaeude->codex);

            // Alte Daten sammeln
            $alteDaten = [
                'codex' => $gebaeude->codex,
                'gebaeude_name' => $gebaeude->gebaeude_name,
                'strasse' => $gebaeude->strasse,
                'hausnummer' => $gebaeude->hausnummer,
                'plz' => $gebaeude->plz,
                'wohnort' => $gebaeude->wohnort,
                'land' => $gebaeude->land,
                'telefon' => $gebaeude->telefon,
                'handy' => $gebaeude->handy,
                'email' => $gebaeude->email,
                'geplante_reinigungen' => $gebaeude->geplante_reinigungen,
                'bemerkung' => $gebaeude->bemerkung,
                'm01' => (bool) $gebaeude->m01,
                'm02' => (bool) $gebaeude->m02,
                'm03' => (bool) $gebaeude->m03,
                'm04' => (bool) $gebaeude->m04,
                'm05' => (bool) $gebaeude->m05,
                'm06' => (bool) $gebaeude->m06,
                'm07' => (bool) $gebaeude->m07,
                'm08' => (bool) $gebaeude->m08,
                'm09' => (bool) $gebaeude->m09,
                'm10' => (bool) $gebaeude->m10,
                'm11' => (bool) $gebaeude->m11,
                'm12' => (bool) $gebaeude->m12,
                'touren' => $gebaeude->touren->pluck('id')->map(fn($id) => (int) $id)->toArray(),
            ];

            // Neue Daten sammeln
            $neueDaten = [
                'codex' => $this->codex,
                'gebaeude_name' => $this->gebaeude_name ?: null,
                'strasse' => $this->strasse,
                'hausnummer' => $this->hausnummer,
                'plz' => $this->plz,
                'wohnort' => $this->wohnort,
                'land' => $this->land,
                'telefon' => $this->telefon ?: null,
                'handy' => $this->handy ?: null,
                'email' => $this->email ?: null,
                'geplante_reinigungen' => (int) ($this->geplante_reinigungen ?? 0),
                'bemerkung' => $this->bemerkung ?: null,
                'm01' => (bool) $this->m01,
                'm02' => (bool) $this->m02,
                'm03' => (bool) $this->m03,
                'm04' => (bool) $this->m04,
                'm05' => (bool) $this->m05,
                'm06' => (bool) $this->m06,
                'm07' => (bool) $this->m07,
                'm08' => (bool) $this->m08,
                'm09' => (bool) $this->m09,
                'm10' => (bool) $this->m10,
                'm11' => (bool) $this->m11,
                'm12' => (bool) $this->m12,
                'touren' => array_map('intval', $this->selectedTouren ?? []),
            ];

            \Log::info('Daten vorbereitet, erstelle Vorschlag...');

            // Änderungsvorschlag erstellen - DIREKT mit DB::table
            $id = \DB::table('gebaeude_aenderungsvorschlaege')->insertGetId([
                'gebaeude_id' => $this->gebaeudeId,
                'user_id' => auth()->id(),
                'typ' => 'aenderung',
                'status' => 'pending',
                'alte_daten' => json_encode($alteDaten),
                'neue_daten' => json_encode($neueDaten),
                'bemerkung' => $this->bemerkung_mitarbeiter ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            \Log::info('Vorschlag erstellt mit ID: ' . $id);

            // Success
            session()->flash('success', 'Änderungsvorschlag wurde erstellt (ID: ' . $id . ')');
            
            // Modal schließen
            $this->modalSchliessen();
            
        } catch (\Exception $e) {
            \Log::error('FEHLER: ' . $e->getMessage());
            \Log::error('Stack: ' . $e->getTraceAsString());
            $this->addError('general', 'Fehler: ' . $e->getMessage());
        }
        
        \Log::info('=== aenderungVorschlagen ENDE ===');
    }

    // ═══════════════════════════════════════════════════════════
    // 🛠️ HELPER-METHODEN
    // ═══════════════════════════════════════════════════════════
    
    private function getMonateArray(): array
    {
        return [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mär', 4 => 'Apr',
            5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Dez',
        ];
    }
}
