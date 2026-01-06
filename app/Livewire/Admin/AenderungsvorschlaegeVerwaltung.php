<?php
// app/Livewire/Admin/AenderungsvorschlaegeVerwaltung.php

namespace App\Livewire\Admin;

use App\Models\GebaeudeAenderungsvorschlag;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class AenderungsvorschlaegeVerwaltung extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ═══════════════════════════════════════════════════════════
    // 🔍 FILTER
    // ═══════════════════════════════════════════════════════════
    
    public $filterTyp = ''; // 'neu', 'aenderung'
    public $filterStatus = 'pending'; // 'pending', 'approved', 'rejected', ''
    public $filterMitarbeiter = '';

    // ═══════════════════════════════════════════════════════════
    // 📝 DETAILANSICHT
    // ═══════════════════════════════════════════════════════════
    
    public $showDetailModal = false;
    public $selectedVorschlag = null;

    // ═══════════════════════════════════════════════════════════
    // ✅ GENEHMIGEN/ABLEHNEN
    // ═══════════════════════════════════════════════════════════
    
    public $showAblehnenModal = false;
    public $ablehnungsgrund = '';
    public $vorschlagIdZumAblehnen = null;

    // ═══════════════════════════════════════════════════════════
    // 🎬 LIFECYCLE
    // ═══════════════════════════════════════════════════════════
    
    public function render()
    {
        // Query aufbauen
        $query = GebaeudeAenderungsvorschlag::query()
            ->with(['gebaeude', 'ersteller', 'bearbeiter'])
            ->neueste();

        // Filter: Typ
        if (!empty($this->filterTyp)) {
            if ($this->filterTyp === 'neu') {
                $query->neueGebaeude();
            } elseif ($this->filterTyp === 'aenderung') {
                $query->aenderungen();
            }
        }

        // Filter: Status
        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        // Filter: Mitarbeiter
        if (!empty($this->filterMitarbeiter)) {
            $query->vonMitarbeiter($this->filterMitarbeiter);
        }

        // Pagination
        $vorschlaege = $query->paginate(20);

        // Mitarbeiter für Filter
        $mitarbeiter = User::mitarbeiter()
            ->orderBy('name')
            ->get(['id', 'name']);

        // Statistiken
        $stats = [
            'pending' => GebaeudeAenderungsvorschlag::pending()->count(),
            'neue_gebaeude' => GebaeudeAenderungsvorschlag::pending()->neueGebaeude()->count(),
            'aenderungen' => GebaeudeAenderungsvorschlag::pending()->aenderungen()->count(),
        ];

        return view('livewire.admin.aenderungsvorschlaege-verwaltung', [
            'vorschlaege' => $vorschlaege,
            'mitarbeiter' => $mitarbeiter,
            'stats' => $stats,
        ])->layout('layouts.app');
    }

    // ═══════════════════════════════════════════════════════════
    // 🔍 FILTER-EVENTS
    // ═══════════════════════════════════════════════════════════
    
    public function updatingFilterTyp()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterMitarbeiter()
    {
        $this->resetPage();
    }

    public function filterZuruecksetzen()
    {
        $this->reset(['filterTyp', 'filterMitarbeiter']);
        $this->filterStatus = 'pending';
        $this->resetPage();
    }

    // ═══════════════════════════════════════════════════════════
    // 📝 DETAILS ANZEIGEN
    // ═══════════════════════════════════════════════════════════
    
    public function detailsAnzeigen(int $id)
    {
        $this->selectedVorschlag = GebaeudeAenderungsvorschlag::with(['gebaeude', 'ersteller', 'bearbeiter'])
            ->findOrFail($id);
        
        $this->showDetailModal = true;
    }

    public function detailModalSchliessen()
    {
        $this->showDetailModal = false;
        $this->selectedVorschlag = null;
    }

    // ═══════════════════════════════════════════════════════════
    // ✅ GENEHMIGEN
    // ═══════════════════════════════════════════════════════════
    
    public function genehmigen(int $id)
    {
        $vorschlag = GebaeudeAenderungsvorschlag::findOrFail($id);

        if (!$vorschlag->istPending()) {
            session()->flash('error', 'Dieser Vorschlag wurde bereits bearbeitet.');
            return;
        }

        // Vorschlag genehmigen
        $erfolg = $vorschlag->genehmigen(auth()->user());

        if ($erfolg) {
            session()->flash('success', 
                $vorschlag->istNeuesGebaeude() 
                    ? 'Neues Gebäude wurde erstellt.' 
                    : 'Änderungen wurden übernommen.'
            );
        } else {
            session()->flash('error', 'Fehler beim Genehmigen des Vorschlags.');
        }

        // Modal schließen falls offen
        $this->detailModalSchliessen();
    }

    // ═══════════════════════════════════════════════════════════
    // ❌ ABLEHNEN
    // ═══════════════════════════════════════════════════════════
    
    public function ablehnenModalOeffnen(int $id)
    {
        $this->vorschlagIdZumAblehnen = $id;
        $this->ablehnungsgrund = '';
        $this->showAblehnenModal = true;
    }

    public function ablehnenModalSchliessen()
    {
        $this->showAblehnenModal = false;
        $this->reset(['vorschlagIdZumAblehnen', 'ablehnungsgrund']);
    }

    public function ablehnen()
    {
        $this->validate([
            'ablehnungsgrund' => 'required|string|max:1000',
        ], [
            'ablehnungsgrund.required' => 'Bitte Ablehnungsgrund angeben',
        ]);

        $vorschlag = GebaeudeAenderungsvorschlag::findOrFail($this->vorschlagIdZumAblehnen);

        if (!$vorschlag->istPending()) {
            session()->flash('error', 'Dieser Vorschlag wurde bereits bearbeitet.');
            $this->ablehnenModalSchliessen();
            return;
        }

        // Vorschlag ablehnen
        $vorschlag->ablehnen(auth()->user(), $this->ablehnungsgrund);

        session()->flash('success', 'Vorschlag wurde abgelehnt.');

        // Modals schließen
        $this->ablehnenModalSchliessen();
        $this->detailModalSchliessen();
    }

    // ═══════════════════════════════════════════════════════════
    // 🗑️ LÖSCHEN
    // ═══════════════════════════════════════════════════════════
    
    public function loeschen(int $id)
    {
        $vorschlag = GebaeudeAenderungsvorschlag::findOrFail($id);
        
        // Nur abgelehnte oder genehmigte Vorschläge können gelöscht werden
        if ($vorschlag->istPending()) {
            session()->flash('error', 'Ausstehende Vorschläge können nicht gelöscht werden. Bitte zuerst genehmigen oder ablehnen.');
            return;
        }

        $vorschlag->delete();
        session()->flash('success', 'Vorschlag wurde gelöscht.');
    }

    // ═══════════════════════════════════════════════════════════
    // 🛠️ HELPER
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Feldnamen übersetzen für bessere Lesbarkeit
     */
    private function uebersetzeFeldname(string $feld): string
    {
        $uebersetzungen = [
            'codex' => 'Codex',
            'gebaeude_name' => 'Gebäude-Name',
            'strasse' => 'Straße',
            'hausnummer' => 'Hausnummer',
            'plz' => 'PLZ',
            'wohnort' => 'Wohnort',
            'land' => 'Land',
            'telefon' => 'Telefon',
            'handy' => 'Handy',
            'email' => 'E-Mail',
            'geplante_reinigungen' => 'Geplante Reinigungen',
            'bemerkung' => 'Bemerkung',
            'm01' => 'Januar',
            'm02' => 'Februar',
            'm03' => 'März',
            'm04' => 'April',
            'm05' => 'Mai',
            'm06' => 'Juni',
            'm07' => 'Juli',
            'm08' => 'August',
            'm09' => 'September',
            'm10' => 'Oktober',
            'm11' => 'November',
            'm12' => 'Dezember',
        ];

        return $uebersetzungen[$feld] ?? $feld;
    }

    /**
     * Formatiert Werte für Anzeige
     */
    private function formatiereWert($wert): string
    {
        if (is_null($wert)) {
            return '<span class="text-muted">-</span>';
        }

        if (is_bool($wert)) {
            return $wert 
                ? '<span class="badge bg-success">Ja</span>' 
                : '<span class="badge bg-secondary">Nein</span>';
        }

        if (is_array($wert)) {
            return implode(', ', $wert);
        }

        return htmlspecialchars($wert);
    }
}
