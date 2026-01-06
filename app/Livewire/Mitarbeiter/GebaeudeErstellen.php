<?php
// app/Livewire/Mitarbeiter/GebaeudeErstellen.php

namespace App\Livewire\Mitarbeiter;

use App\Models\GebaeudeAenderungsvorschlag;
use App\Models\Tour;
use Livewire\Component;

class GebaeudeErstellen extends Component
{
    // ═══════════════════════════════════════════════════════════
    // 📋 FORMULAR-FELDER
    // ═══════════════════════════════════════════════════════════
    
    // Basis-Daten
    public $codex;
    public $gebaeude_name;
    public $strasse;
    public $hausnummer;
    public $plz;
    public $wohnort;
    public $land = 'IT';
    
    // Kontakt-Daten
    public $telefon;
    public $handy;
    public $email;
    
    // Reinigung
    public $geplante_reinigungen;
    public $bemerkung;
    
    // Monate (aktive Monate)
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
    
    // Bemerkung für Admin
    public $bemerkung_mitarbeiter;
    
    // Success-State
    public $showSuccess = false;

    // ═══════════════════════════════════════════════════════════
    // 🎯 VALIDATION RULES
    // ═══════════════════════════════════════════════════════════
    
    protected $rules = [
        'codex' => 'required|string|max:50|unique:gebaeude,codex',
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
    
    public function mount()
    {
        // Standard-Werte
        $this->land = 'IT';
        $this->geplante_reinigungen = 12;
    }

    public function render()
    {
        // Alle Touren für Auswahl laden
        $touren = Tour::where('aktiv', true)
            ->orderBy('name')
            ->get();

        return view('livewire.mitarbeiter.gebaeude-erstellen', [
            'touren' => $touren,
            'monate' => $this->getMonateArray(),
        ])->layout('layouts.mitarbeiter');
    }

    // ═══════════════════════════════════════════════════════════
    // 💾 SPEICHERN
    // ═══════════════════════════════════════════════════════════
    
    public function speichern()
    {
        $this->validate();

        // Gebäude-Daten vorbereiten
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
            'm01' => $this->m01,
            'm02' => $this->m02,
            'm03' => $this->m03,
            'm04' => $this->m04,
            'm05' => $this->m05,
            'm06' => $this->m06,
            'm07' => $this->m07,
            'm08' => $this->m08,
            'm09' => $this->m09,
            'm10' => $this->m10,
            'm11' => $this->m11,
            'm12' => $this->m12,
        ];

        // Änderungsvorschlag erstellen
        $vorschlag = GebaeudeAenderungsvorschlag::erstelleVorschlag(
            mitarbeiter: auth()->user(),
            typ: 'neu',
            neueDaten: $neueDaten,
            bemerkung: $this->bemerkung_mitarbeiter
        );

        // TODO: Touren-Zuordnung in neue_daten speichern (für Admin)
        if (!empty($this->selectedTouren)) {
            $alteDaten = $vorschlag->neue_daten;
            $alteDaten['touren'] = $this->selectedTouren;
            $vorschlag->update(['neue_daten' => $alteDaten]);
        }

        // Success anzeigen
        $this->showSuccess = true;
        
        // Formular zurücksetzen
        $this->reset([
            'codex', 'gebaeude_name', 'strasse', 'hausnummer', 
            'plz', 'wohnort', 'telefon', 'handy', 'email',
            'geplante_reinigungen', 'bemerkung', 'bemerkung_mitarbeiter',
            'selectedTouren'
        ]);
        
        // Monate zurücksetzen
        foreach (range(1, 12) as $monat) {
            $key = 'm' . str_pad($monat, 2, '0', STR_PAD_LEFT);
            $this->$key = false;
        }

        session()->flash('success', 'Gebäude-Vorschlag wurde erstellt und wartet auf Freigabe durch einen Admin.');
    }

    // ═══════════════════════════════════════════════════════════
    // 🛠️ HELPER-METHODEN
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Alle Monate markieren
     */
    public function alleMonateMarkieren()
    {
        foreach (range(1, 12) as $monat) {
            $key = 'm' . str_pad($monat, 2, '0', STR_PAD_LEFT);
            $this->$key = true;
        }
    }

    /**
     * Alle Monate abwählen
     */
    public function keineMonateMarkieren()
    {
        foreach (range(1, 12) as $monat) {
            $key = 'm' . str_pad($monat, 2, '0', STR_PAD_LEFT);
            $this->$key = false;
        }
    }

    /**
     * Monatsnamen-Array
     */
    private function getMonateArray(): array
    {
        return [
            1 => 'Januar',
            2 => 'Februar',
            3 => 'März',
            4 => 'April',
            5 => 'Mai',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'August',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Dezember',
        ];
    }

    /**
     * Success-Message schließen
     */
    public function closeSuccess()
    {
        $this->showSuccess = false;
    }
}
