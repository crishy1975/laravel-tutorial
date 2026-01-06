<?php
// app/Livewire/Mitarbeiter/Reinigungsplanung.php

namespace App\Livewire\Mitarbeiter;

use App\Models\Gebaeude;
use App\Models\Tour;
use App\Services\FaelligkeitsService;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Reinigungsplanung extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ═══════════════════════════════════════════════════════════
    // 🔍 FILTER
    // ═══════════════════════════════════════════════════════════
    
    public $filterTour = '';
    public $filterMonat = '';
    public $filterStatus = ''; // 'offen', 'erledigt'
    public $suchbegriff = '';

    // ═══════════════════════════════════════════════════════════
    // 📝 ERLEDIGT MARKIEREN
    // ═══════════════════════════════════════════════════════════
    
    public $showErledigtModal = false;
    public $erledigtGebaeudeId = null;
    public $erledigtDatum;
    public $erledigtBemerkung = '';

    // ═══════════════════════════════════════════════════════════
    // 🎬 LIFECYCLE
    // ═══════════════════════════════════════════════════════════
    
    public function __construct(
        protected FaelligkeitsService $faelligkeitsService
    ) {
        parent::__construct('livewire.mitarbeiter.reinigungsplanung');
    }

    public function mount()
    {
        // Standard: Aktueller Monat
        $this->filterMonat = now()->month;
        $this->erledigtDatum = today()->format('Y-m-d');
    }

    public function render()
    {
        // Query aufbauen
        $query = Gebaeude::query()->with(['touren', 'timelines' => function($q) {
            $q->orderBy('datum', 'desc')->limit(1);
        }]);

        // Filter: Monat (aktive Monate)
        if (!empty($this->filterMonat) && $this->filterMonat >= 1 && $this->filterMonat <= 12) {
            $monatFeld = 'm' . str_pad($this->filterMonat, 2, '0', STR_PAD_LEFT);
            $query->where($monatFeld, true);
        }

        // Filter: Tour
        if (!empty($this->filterTour)) {
            $query->whereHas('touren', function($q) {
                $q->where('tour.id', $this->filterTour);
            });
        }

        // Filter: Suchbegriff
        if (!empty($this->suchbegriff)) {
            $query->where(function($q) {
                $q->where('codex', 'LIKE', '%' . $this->suchbegriff . '%')
                  ->orWhere('gebaeude_name', 'LIKE', '%' . $this->suchbegriff . '%')
                  ->orWhere('strasse', 'LIKE', '%' . $this->suchbegriff . '%')
                  ->orWhere('wohnort', 'LIKE', '%' . $this->suchbegriff . '%');
            });
        }

        // Sortierung
        $query->orderBy('strasse')
              ->orderByRaw('CAST(hausnummer AS UNSIGNED)')
              ->orderBy('hausnummer');

        // Gebäude laden
        $gebaeude = $query->get();

        // Fälligkeit berechnen mit Service
        $gebaeude = $gebaeude->map(function ($g) {
            $letzteReinigung = $this->faelligkeitsService->getLetzteReinigung($g);
            $naechsteFaelligkeit = $this->faelligkeitsService->getNaechsteFaelligkeit($g);
            $istFaellig = $this->faelligkeitsService->istFaellig($g);

            $g->letzte_reinigung_datum = $letzteReinigung;
            $g->ist_erledigt = !$istFaellig;
            $g->naechste_faelligkeit = $naechsteFaelligkeit;

            return $g;
        });

        // Filter: Status
        if ($this->filterStatus === 'offen') {
            $gebaeude = $gebaeude->filter(fn($g) => !$g->ist_erledigt);
        } elseif ($this->filterStatus === 'erledigt') {
            $gebaeude = $gebaeude->filter(fn($g) => $g->ist_erledigt);
        }

        // Statistiken
        $stats = [
            'gesamt'   => $gebaeude->count(),
            'offen'    => $gebaeude->filter(fn($g) => !$g->ist_erledigt)->count(),
            'erledigt' => $gebaeude->filter(fn($g) => $g->ist_erledigt)->count(),
        ];

        // Pagination
        $perPage = 20;
        $currentPage = $this->getPage();
        $pagedData = $gebaeude->forPage($currentPage, $perPage);
        
        $gebaeudePaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData->values(),
            $stats['gesamt'],
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );

        // Touren für Dropdown
        $touren = Tour::where('aktiv', true)->orderBy('name')->get();

        return view('livewire.mitarbeiter.reinigungsplanung', [
            'gebaeude' => $gebaeudePaginated,
            'touren' => $touren,
            'stats' => $stats,
            'monate' => $this->getMonateArray(),
        ])->layout('layouts.app');
    }

    // ═══════════════════════════════════════════════════════════
    // 🔍 FILTER-EVENTS
    // ═══════════════════════════════════════════════════════════
    
    public function updatingFilterTour()
    {
        $this->resetPage();
    }

    public function updatingFilterMonat()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingSuchbegriff()
    {
        $this->resetPage();
    }

    public function filterZuruecksetzen()
    {
        $this->reset(['filterTour', 'filterMonat', 'filterStatus', 'suchbegriff']);
        $this->filterMonat = now()->month;
        $this->resetPage();
    }

    // ═══════════════════════════════════════════════════════════
    // ✅ ERLEDIGT MARKIEREN
    // ═══════════════════════════════════════════════════════════
    
    public function erledigtModalOeffnen(int $gebaeudeId)
    {
        $this->erledigtGebaeudeId = $gebaeudeId;
        $this->erledigtDatum = today()->format('Y-m-d');
        $this->erledigtBemerkung = '';
        $this->showErledigtModal = true;
    }

    public function erledigtModalSchliessen()
    {
        $this->showErledigtModal = false;
        $this->reset(['erledigtGebaeudeId', 'erledigtDatum', 'erledigtBemerkung']);
    }

    public function erledigtSpeichern()
    {
        $this->validate([
            'erledigtDatum' => 'required|date',
            'erledigtBemerkung' => 'nullable|string|max:500',
        ]);

        $gebaeude = Gebaeude::findOrFail($this->erledigtGebaeudeId);
        $datum = Carbon::parse($this->erledigtDatum);

        // Timeline-Eintrag erstellen
        $gebaeude->timelines()->create([
            'datum'       => $datum,
            'bemerkung'   => $this->erledigtBemerkung ?: 'Reinigung durchgeführt',
            'person_id'   => auth()->id(),
            'person_name' => auth()->user()->name,
        ]);

        // Gebäude aktualisieren
        $updateData = ['letzter_termin' => $datum];
        
        if ($gebaeude->fattura_profile_id) {
            $updateData['rechnung_schreiben'] = true;
        }
        
        $gebaeude->update($updateData);

        // Fälligkeit über Service neu berechnen
        $this->faelligkeitsService->aktualisiereGebaeude($gebaeude);

        // Success
        session()->flash('success', 'Reinigung für ' . ($gebaeude->gebaeude_name ?: $gebaeude->codex) . ' wurde eingetragen.');
        
        // Modal schließen
        $this->erledigtModalSchliessen();
    }

    // ═══════════════════════════════════════════════════════════
    // 🛠️ HELPER
    // ═══════════════════════════════════════════════════════════
    
    private function getMonateArray(): array
    {
        return [
            1  => 'Januar',
            2  => 'Februar',
            3  => 'März',
            4  => 'April',
            5  => 'Mai',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'August',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Dezember',
        ];
    }
}
