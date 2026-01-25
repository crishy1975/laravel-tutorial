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
use App\Helpers\FeiertagHelper;
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

    // Zahlungsexport (Checkboxen)
    public array $ausgewaehlteRechnungen = [];
    public bool $alleAusgewaehlt = false;

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
    // 💳 ZAHLUNGSEXPORT
    // ═══════════════════════════════════════════════════════════

    /**
     * Einzelne Rechnung auswählen/abwählen
     */
    public function toggleAuswahl(int $id): void
    {
        if (in_array($id, $this->ausgewaehlteRechnungen)) {
            $this->ausgewaehlteRechnungen = array_values(
                array_diff($this->ausgewaehlteRechnungen, [$id])
            );
        } else {
            $this->ausgewaehlteRechnungen[] = $id;
        }
        
        $this->updateAlleAusgewaehlt();
    }

    /**
     * Alle offenen Rechnungen auf aktueller Seite auswählen/abwählen
     */
    public function toggleAlleAuswahl(): void
    {
        $offeneIds = $this->rechnungen
            ->where('status', 'offen')
            ->pluck('id')
            ->toArray();

        if ($this->alleAusgewaehlt) {
            // Alle abwählen
            $this->ausgewaehlteRechnungen = array_values(
                array_diff($this->ausgewaehlteRechnungen, $offeneIds)
            );
        } else {
            // Alle auswählen
            $this->ausgewaehlteRechnungen = array_unique(
                array_merge($this->ausgewaehlteRechnungen, $offeneIds)
            );
        }
        
        $this->alleAusgewaehlt = !$this->alleAusgewaehlt;
    }

    /**
     * Auswahl zurücksetzen
     */
    public function auswahlZuruecksetzen(): void
    {
        $this->ausgewaehlteRechnungen = [];
        $this->alleAusgewaehlt = false;
    }

    /**
     * Prüft ob alle offenen Rechnungen der Seite ausgewählt sind
     */
    protected function updateAlleAusgewaehlt(): void
    {
        $offeneIds = $this->rechnungen
            ->where('status', 'offen')
            ->pluck('id')
            ->toArray();

        $this->alleAusgewaehlt = !empty($offeneIds) && 
            empty(array_diff($offeneIds, $this->ausgewaehlteRechnungen));
    }

    /**
     * Summe der ausgewählten Rechnungen
     */
    public function getAuswahlSummeProperty(): float
    {
        if (empty($this->ausgewaehlteRechnungen)) {
            return 0;
        }

        return Eingangsrechnung::whereIn('id', $this->ausgewaehlteRechnungen)
            ->sum('brutto_betrag');
    }

    /**
     * CSV für Banküberweisung exportieren und als bezahlt markieren
     */
    public function exportierenUndBezahlen()
    {
        if (empty($this->ausgewaehlteRechnungen)) {
            session()->flash('error', 'Keine Rechnungen ausgewählt.');
            return;
        }

        $rechnungen = Eingangsrechnung::with('lieferant')
            ->whereIn('id', $this->ausgewaehlteRechnungen)
            ->where('status', 'offen')
            ->get();

        if ($rechnungen->isEmpty()) {
            session()->flash('error', 'Keine offenen Rechnungen in der Auswahl.');
            return;
        }

        // Prüfen ob alle Lieferanten eine IBAN haben
        $ohneIban = $rechnungen->filter(fn($r) => !$r->lieferant->hatIban());
        if ($ohneIban->isNotEmpty()) {
            $namen = $ohneIban->pluck('lieferant.name')->unique()->implode(', ');
            session()->flash('error', "Fehlende IBAN bei: {$namen}");
            return;
        }

        // CSV erstellen
        $csvData = $this->erstelleCsvDaten($rechnungen);
        
        // Rechnungen als bezahlt markieren
        $this->markiereAlsBezahlt($rechnungen);

        // Auswahl zurücksetzen
        $anzahl = count($this->ausgewaehlteRechnungen);
        $this->auswahlZuruecksetzen();

        session()->flash('success', "{$anzahl} Rechnungen exportiert und als bezahlt markiert.");

        // ⭐ Livewire streamDownload - funktioniert direkt ohne temporäre Dateien
        $filename = 'Zahlungen_' . now()->format('Y-m-d_His') . '.csv';
        
        return response()->streamDownload(function () use ($csvData) {
            $handle = fopen('php://output', 'w');
            // BOM für Excel UTF-8
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($csvData as $row) {
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * CSV-Daten erstellen
     */
    protected function erstelleCsvDaten($rechnungen): array
    {
        $data = [];

        // Header
        $data[] = [
            'Empfänger-Bezeichnung',
            'Empfänger-Adresse',
            'Empfänger-PLZ',
            'Empfänger-Ort',
            'Empfänger-IBAN',
            'Betrag',
            'Beschreibung',
            'Durchführungsdatum',
            'Empfänger-Steuernummer',
        ];

        foreach ($rechnungen as $rechnung) {
            // Zahlungsdatum: Rechnungsdatum + 30 Tage, nächster Bankarbeitstag
            $zahlungsdatum = FeiertagHelper::berechneZahlungsdatum(
                $rechnung->rechnungsdatum,
                30
            );

            $data[] = [
                $rechnung->lieferant->name,
                '', // Adresse
                '', // PLZ
                '', // Ort
                $rechnung->lieferant->iban,
                number_format($rechnung->brutto_betrag, 2, ',', ''), // Betrag mit Komma
                'Rechnung: ' . $rechnung->rechnungsnummer,
                $zahlungsdatum->format('d.m.Y'),
                '', // Steuernummer
            ];
        }

        return $data;
    }

    /**
     * Rechnungen als bezahlt markieren mit Zahlungsvermerk
     */
    protected function markiereAlsBezahlt($rechnungen): void
    {
        foreach ($rechnungen as $rechnung) {
            $zahlungsdatum = FeiertagHelper::berechneZahlungsdatum(
                $rechnung->rechnungsdatum,
                30
            );

            $vermerk = $rechnung->notiz 
                ? $rechnung->notiz . "\n" 
                : '';
            $vermerk .= 'Bankexport am ' . now()->format('d.m.Y') . ', Zahlung geplant: ' . $zahlungsdatum->format('d.m.Y');

            $rechnung->update([
                'status'          => 'bezahlt',
                'zahlungsmethode' => 'bank',
                'bezahlt_am'      => $zahlungsdatum,
                'notiz'           => $vermerk,
            ]);
        }
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
            'auswahlSumme'   => $this->auswahlSumme,
        ])->layout('layouts.app', ['title' => 'Eingangsrechnungen']);
    }
}
