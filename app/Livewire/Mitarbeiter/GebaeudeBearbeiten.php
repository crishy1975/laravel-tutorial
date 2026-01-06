<?php
// app/Livewire/Mitarbeiter/GebaeudeBearbeiten.php

namespace App\Livewire\Mitarbeiter;

use App\Models\Gebaeude;
use App\Models\GebaeudeAenderungsvorschlag;
use App\Models\Tour;
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
    
    // Formular-Felder (gleich wie bei GebaeudeErstellen)
    public $codex;
    public $gebaeude_name;
    public $strasse;
    public $hausnummer;
    public $plz;
    public $wohnort;
    public $land;
    public $telefon;
    public $handy;
    public $email;
    public $geplante_reinigungen;
    public $bemerkung;
    public $bemerkung_mitarbeiter;
    
    // Monate
    public $m01, $m02, $m03, $m04, $m05, $m06;
    public $m07, $m08, $m09, $m10, $m11, $m12;
    
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
        // Gebäude-Liste mit Suche und Filter
        $query = Gebaeude::query()
            ->with(['touren', 'ausstehendeAenderungsvorschlaege']);

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

        // Touren für Filter-Dropdown
        $touren = Tour::where('aktiv', true)
            ->orderBy('name')
            ->get();

        return view('livewire.mitarbeiter.gebaeude-bearbeiten', [
            'gebaeudeListe' => $gebaeude,
            'touren' => $touren,
            'monate' => $this->getMonateArray(),
        ])->layout('layouts.app');
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
        $this->codex = $this->gebaeude->codex;
        $this->gebaeude_name = $this->gebaeude->gebaeude_name;
        $this->strasse = $this->gebaeude->strasse;
        $this->hausnummer = $this->gebaeude->hausnummer;
        $this->plz = $this->gebaeude->plz;
        $this->wohnort = $this->gebaeude->wohnort;
        $this->land = $this->gebaeude->land;
        $this->telefon = $this->gebaeude->telefon;
        $this->handy = $this->gebaeude->handy;
        $this->email = $this->gebaeude->email;
        $this->geplante_reinigungen = $this->gebaeude->geplante_reinigungen;
        $this->bemerkung = $this->gebaeude->bemerkung;
        
        // Monate
        foreach (range(1, 12) as $monat) {
            $key = 'm' . str_pad($monat, 2, '0', STR_PAD_LEFT);
            $this->$key = $this->gebaeude->$key;
        }

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
            'bemerkung', 'bemerkung_mitarbeiter', 'selectedTouren'
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 💾 SPEICHERN (als Änderungsvorschlag)
    // ═══════════════════════════════════════════════════════════
    
    public function aenderungVorschlagen()
    {
        $this->validate();

        // Neue Daten sammeln
        $neueDaten = [
            'codex' => $this->codex,
            'gebaeude_name' => $this->gebaeude_name,
            'strasse' => $this->strasse,
            'hausnummer' => $this->hausnummer,
            'plz' => $this->plz,
            'wohnort' => $this->wohnort,
            'land' => $this->land,
            'telefon' => $this->telefon,
            'handy' => $this->handy,
            'email' => $this->email,
            'geplante_reinigungen' => $this->geplante_reinigungen,
            'bemerkung' => $this->bemerkung,
        ];

        // Monate
        foreach (range(1, 12) as $monat) {
            $key = 'm' . str_pad($monat, 2, '0', STR_PAD_LEFT);
            $neueDaten[$key] = $this->$key;
        }

        // Änderungsvorschlag erstellen
        $vorschlag = GebaeudeAenderungsvorschlag::erstelleVorschlag(
            mitarbeiter: auth()->user(),
            typ: 'aenderung',
            neueDaten: $neueDaten,
            gebaeudeId: $this->gebaeudeId,
            bemerkung: $this->bemerkung_mitarbeiter
        );

        // Touren-Zuordnung speichern
        if (!empty($this->selectedTouren)) {
            $alteDaten = $vorschlag->neue_daten;
            $alteDaten['touren'] = $this->selectedTouren;
            $vorschlag->update(['neue_daten' => $alteDaten]);
        }

        // Success
        session()->flash('success', 'Änderungsvorschlag wurde erstellt und wartet auf Freigabe durch einen Admin.');
        
        // Modal schließen
        $this->modalSchliessen();
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
