<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: EingangsrechnungenVerwaltung.php
 * PFAD:  app/Livewire/Admin/EingangsrechnungenVerwaltung.php
 * ════════════════════════════════════════════════════════════════════════════
 */

namespace App\Livewire\Admin;

use App\Models\Lieferant;
use App\Models\Eingangsrechnung;
use App\Services\FatturaImportService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;

class EingangsrechnungenVerwaltung extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    // ═══════════════════════════════════════════════════════════
    // 📋 EIGENSCHAFTEN
    // ═══════════════════════════════════════════════════════════

    // Filter
    public string $filterStatus = '';
    public string $filterLieferant = '';
    public string $suchbegriff = '';
    public string $sortierSpalte = 'rechnungsdatum';
    public string $sortierRichtung = 'desc';

    // Ansicht
    public string $ansicht = 'rechnungen'; // 'rechnungen' oder 'lieferanten'

    // Upload
    public $uploadDatei;
    public array $importErgebnis = [];
    public bool $showImportModal = false;

    // Rechnung bearbeiten
    public ?int $bearbeitenId = null;
    public string $bearbeitenStatus = '';
    public string $bearbeitenZahlungsmethode = '';
    public ?string $bearbeitenBezahltAm = null;
    public string $bearbeitenNotiz = '';

    // Lieferant bearbeiten
    public ?int $lieferantBearbeitenId = null;
    public string $lieferantIban = '';
    public string $lieferantNotiz = '';

    // Detail-Ansicht
    public ?int $detailRechnungId = null;

    // ═══════════════════════════════════════════════════════════
    // 🔄 LIFECYCLE
    // ═══════════════════════════════════════════════════════════

    public function mount(): void
    {
        // Standard-Ansicht
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterLieferant(): void
    {
        $this->resetPage();
    }

    public function updatingSuchbegriff(): void
    {
        $this->resetPage();
    }

    // ═══════════════════════════════════════════════════════════
    // 📤 IMPORT
    // ═══════════════════════════════════════════════════════════

    public function importStarten(): void
    {
        $this->validate([
            'uploadDatei' => 'required|file|mimes:xml,zip|max:51200', // Max 50MB
        ], [
            'uploadDatei.required' => 'Bitte eine Datei auswählen.',
            'uploadDatei.mimes'    => 'Nur XML- und ZIP-Dateien erlaubt.',
            'uploadDatei.max'      => 'Datei darf maximal 50MB groß sein.',
        ]);

        $service = new FatturaImportService();
        $this->importErgebnis = $service->importFromUpload($this->uploadDatei);

        $this->uploadDatei = null;
        $this->showImportModal = true;
    }

    public function importModalSchliessen(): void
    {
        $this->showImportModal = false;
        $this->importErgebnis = [];
    }

    // ═══════════════════════════════════════════════════════════
    // ✏️ RECHNUNG BEARBEITEN
    // ═══════════════════════════════════════════════════════════

    public function rechnungBearbeiten(int $id): void
    {
        $rechnung = Eingangsrechnung::find($id);
        
        if (!$rechnung) {
            return;
        }

        $this->bearbeitenId = $id;
        $this->bearbeitenStatus = $rechnung->status;
        $this->bearbeitenZahlungsmethode = $rechnung->zahlungsmethode ?? '';
        $this->bearbeitenBezahltAm = $rechnung->bezahlt_am?->format('Y-m-d');
        $this->bearbeitenNotiz = $rechnung->notiz ?? '';
    }

    public function rechnungSpeichern(): void
    {
        $rechnung = Eingangsrechnung::find($this->bearbeitenId);
        
        if (!$rechnung) {
            return;
        }

        $rechnung->update([
            'status'          => $this->bearbeitenStatus,
            'zahlungsmethode' => $this->bearbeitenZahlungsmethode ?: null,
            'bezahlt_am'      => $this->bearbeitenStatus === 'bezahlt' ? ($this->bearbeitenBezahltAm ?: now()) : null,
            'notiz'           => $this->bearbeitenNotiz ?: null,
        ]);

        $this->bearbeitenAbbrechen();
        
        session()->flash('success', 'Rechnung wurde aktualisiert.');
    }

    public function bearbeitenAbbrechen(): void
    {
        $this->bearbeitenId = null;
        $this->bearbeitenStatus = '';
        $this->bearbeitenZahlungsmethode = '';
        $this->bearbeitenBezahltAm = null;
        $this->bearbeitenNotiz = '';
    }

    // ═══════════════════════════════════════════════════════════
    // ⚡ SCHNELLAKTIONEN
    // ═══════════════════════════════════════════════════════════

    public function schnellBezahlt(int $id, string $methode = 'bank'): void
    {
        $rechnung = Eingangsrechnung::find($id);
        
        if ($rechnung) {
            $rechnung->markiereAlsBezahlt($methode);
            session()->flash('success', "Rechnung {$rechnung->rechnungsnummer} als bezahlt markiert.");
        }
    }

    public function schnellIgnoriert(int $id): void
    {
        $rechnung = Eingangsrechnung::find($id);
        
        if ($rechnung) {
            $rechnung->markiereAlsIgnoriert();
            session()->flash('success', "Rechnung {$rechnung->rechnungsnummer} als ignoriert markiert.");
        }
    }

    public function schnellWiederOeffnen(int $id): void
    {
        $rechnung = Eingangsrechnung::find($id);
        
        if ($rechnung) {
            $rechnung->wiederOeffnen();
            session()->flash('success', "Rechnung {$rechnung->rechnungsnummer} wieder geöffnet.");
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 🏢 LIEFERANT BEARBEITEN
    // ═══════════════════════════════════════════════════════════

    public function lieferantBearbeiten(int $id): void
    {
        $lieferant = Lieferant::find($id);
        
        if (!$lieferant) {
            return;
        }

        $this->lieferantBearbeitenId = $id;
        $this->lieferantIban = $lieferant->iban ?? '';
        $this->lieferantNotiz = $lieferant->notiz ?? '';
    }

    public function lieferantSpeichern(): void
    {
        $lieferant = Lieferant::find($this->lieferantBearbeitenId);
        
        if (!$lieferant) {
            return;
        }

        // IBAN validieren (einfache Prüfung)
        $iban = strtoupper(str_replace(' ', '', $this->lieferantIban));
        
        if ($iban && strlen($iban) < 15) {
            session()->flash('error', 'IBAN zu kurz.');
            return;
        }

        $lieferant->update([
            'iban'  => $iban ?: null,
            'notiz' => $this->lieferantNotiz ?: null,
        ]);

        $this->lieferantBearbeitenAbbrechen();
        
        session()->flash('success', 'Lieferant wurde aktualisiert.');
    }

    public function lieferantBearbeitenAbbrechen(): void
    {
        $this->lieferantBearbeitenId = null;
        $this->lieferantIban = '';
        $this->lieferantNotiz = '';
    }

    // ═══════════════════════════════════════════════════════════
    // 🔍 DETAIL-ANSICHT
    // ═══════════════════════════════════════════════════════════

    public function detailAnzeigen(int $id): void
    {
        $this->detailRechnungId = $id;
    }

    public function detailSchliessen(): void
    {
        $this->detailRechnungId = null;
    }

    // ═══════════════════════════════════════════════════════════
    // 🏢 LIEFERANTEN-RECHNUNGEN ANZEIGEN
    // ═══════════════════════════════════════════════════════════

    public function zeigeRechnungenVonLieferant(int $id): void
    {
        $this->filterLieferant = (string) $id;
        $this->filterStatus = '';
        $this->suchbegriff = '';
        $this->ansicht = 'rechnungen';
        $this->resetPage();
    }

    public function filterZuruecksetzen(): void
    {
        $this->filterLieferant = '';
        $this->filterStatus = '';
        $this->suchbegriff = '';
        $this->resetPage();
    }

    // ═══════════════════════════════════════════════════════════
    // 🔀 SORTIERUNG
    // ═══════════════════════════════════════════════════════════

    public function sortieren(string $spalte): void
    {
        if ($this->sortierSpalte === $spalte) {
            $this->sortierRichtung = $this->sortierRichtung === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortierSpalte = $spalte;
            $this->sortierRichtung = 'asc';
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 📊 DATEN LADEN
    // ═══════════════════════════════════════════════════════════

    public function getRechnungenProperty()
    {
        $query = Eingangsrechnung::with('lieferant');

        // Filter: Status
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Filter: Lieferant
        if ($this->filterLieferant) {
            $query->where('lieferant_id', $this->filterLieferant);
        }

        // Suche
        if ($this->suchbegriff) {
            $suche = $this->suchbegriff;
            $query->where(function ($q) use ($suche) {
                $q->where('rechnungsnummer', 'like', "%{$suche}%")
                  ->orWhereHas('lieferant', function ($lq) use ($suche) {
                      $lq->where('name', 'like', "%{$suche}%");
                  });
            });
        }

        // Sortierung
        if ($this->sortierSpalte === 'lieferant') {
            $query->join('lieferanten', 'eingangsrechnungen.lieferant_id', '=', 'lieferanten.id')
                  ->orderBy('lieferanten.name', $this->sortierRichtung)
                  ->select('eingangsrechnungen.*');
        } else {
            $query->orderBy($this->sortierSpalte, $this->sortierRichtung);
        }

        return $query->paginate(20);
    }

    /**
     * Summen für gefilterte Rechnungen (für Fußzeile)
     */
    public function getFilterSummenProperty(): array
    {
        $query = Eingangsrechnung::query();

        // Filter: Status
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Filter: Lieferant
        if ($this->filterLieferant) {
            $query->where('lieferant_id', $this->filterLieferant);
        }

        // Suche
        if ($this->suchbegriff) {
            $suche = $this->suchbegriff;
            $query->where(function ($q) use ($suche) {
                $q->where('rechnungsnummer', 'like', "%{$suche}%")
                  ->orWhereHas('lieferant', function ($lq) use ($suche) {
                      $lq->where('name', 'like', "%{$suche}%");
                  });
            });
        }

        // Summen berechnen
        $gesamt = (clone $query)->sum('brutto_betrag');
        $offen = (clone $query)->where('status', 'offen')->sum('brutto_betrag');
        $bezahlt = (clone $query)->where('status', 'bezahlt')->sum('brutto_betrag');
        $anzahl = (clone $query)->count();

        return [
            'anzahl'  => $anzahl,
            'gesamt'  => $gesamt,
            'offen'   => $offen,
            'bezahlt' => $bezahlt,
        ];
    }

    public function getLieferantenProperty()
    {
        return Lieferant::withCount(['eingangsrechnungen', 'offeneRechnungen'])
            ->withSum('eingangsrechnungen as summe_gesamt', 'brutto_betrag')
            ->withSum('offeneRechnungen as summe_offen', 'brutto_betrag')
            ->orderBy('name')
            ->get();
    }

    public function getStatistikProperty(): array
    {
        return [
            'gesamt'      => Eingangsrechnung::count(),
            'offen'       => Eingangsrechnung::offen()->count(),
            'bezahlt'     => Eingangsrechnung::bezahlt()->count(),
            'ignoriert'   => Eingangsrechnung::ignoriert()->count(),
            'summe_offen' => Eingangsrechnung::offen()->sum('brutto_betrag'),
            'lieferanten' => Lieferant::count(),
        ];
    }

    public function getDetailRechnungProperty()
    {
        if (!$this->detailRechnungId) {
            return null;
        }

        return Eingangsrechnung::with(['lieferant', 'artikel'])->find($this->detailRechnungId);
    }

    // ═══════════════════════════════════════════════════════════
    // 🎨 RENDER
    // ═══════════════════════════════════════════════════════════

    public function render()
    {
        return view('livewire.admin.eingangsrechnungen-verwaltung', [
            'rechnungen'     => $this->rechnungen,
            'lieferanten'    => $this->lieferanten,
            'statistik'      => $this->statistik,
            'filterSummen'   => $this->filterSummen,
            'detailRechnung' => $this->detailRechnung,
        ])->layout('layouts.app', ['title' => 'Eingangsrechnungen']);
    }
}
