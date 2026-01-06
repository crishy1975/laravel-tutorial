<?php
// app/Livewire/Mitarbeiter/Reinigungsplanung.php

namespace App\Livewire\Mitarbeiter;

use App\Models\Gebaeude;
use App\Models\Tour;
use App\Models\GebaeudeAenderungsvorschlag;
use App\Services\FaelligkeitsService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.mitarbeiter')]
class Reinigungsplanung extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ═══════════════════════════════════════════════════════════
    // 🔍 FILTER
    // ═══════════════════════════════════════════════════════════
    
    public $filterTour = '';
    public $filterMonat = '';
    public $filterStatus = '';
    public $suchbegriff = '';

    // ═══════════════════════════════════════════════════════════
    // ✅ ERLEDIGT MARKIEREN
    // ═══════════════════════════════════════════════════════════
    
    public $showErledigtModal = false;
    public $erledigtGebaeudeId = null;
    public $erledigtDatum;
    public $erledigtBemerkung = '';

    // ═══════════════════════════════════════════════════════════
    // ✏️ BEARBEITEN MODAL
    // ═══════════════════════════════════════════════════════════
    
    public $showBearbeitenModal = false;
    public $bearbeitenGebaeudeId = null;
    public $bearbeitenGebaeude = null;
    
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
    public $bemerkung = '';
    public $bemerkung_mitarbeiter = '';
    public $geplante_reinigungen = 0;
    
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
    // 🎬 LIFECYCLE
    // ═══════════════════════════════════════════════════════════
    
    public function mount()
    {
        $this->filterMonat = now()->month;
        $this->erledigtDatum = today()->format('Y-m-d');
    }

    public function render(FaelligkeitsService $faelligkeitsService)
    {
        $query = Gebaeude::query()->with(['touren', 'timelines' => function($q) {
            $q->orderBy('datum', 'desc')->limit(1);
        }]);

        // Filter: Monat
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

        $gebaeude = $query->get();

        // Fälligkeit berechnen
        $gebaeude = $gebaeude->map(function ($g) use ($faelligkeitsService) {
            $g->letzte_reinigung_datum = $faelligkeitsService->getLetzteReinigung($g);
            $g->naechste_faelligkeit = $faelligkeitsService->getNaechsteFaelligkeit($g);
            $g->ist_erledigt = !$faelligkeitsService->istFaellig($g);
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

        $touren = Tour::where('aktiv', true)->orderBy('name')->get();

        return view('livewire.mitarbeiter.reinigungsplanung', [
            'gebaeude' => $gebaeudePaginated,
            'touren' => $touren,
            'stats' => $stats,
            'monate' => $this->getMonateArray(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 🔍 FILTER-EVENTS
    // ═══════════════════════════════════════════════════════════
    
    public function updatingFilterTour() { $this->resetPage(); }
    public function updatingFilterMonat() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingSuchbegriff() { $this->resetPage(); }

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

        $gebaeude->timelines()->create([
            'datum'       => $datum,
            'bemerkung'   => $this->erledigtBemerkung ?: 'Reinigung durchgeführt',
            'person_id'   => auth()->id(),
            'person_name' => auth()->user()->name,
        ]);

        $updateData = ['letzter_termin' => $datum];
        if ($gebaeude->fattura_profile_id) {
            $updateData['rechnung_schreiben'] = true;
        }
        $gebaeude->update($updateData);

        $faelligkeitsService = app(FaelligkeitsService::class);
        $faelligkeitsService->aktualisiereGebaeude($gebaeude);

        session()->flash('success', 'Reinigung für ' . ($gebaeude->gebaeude_name ?: $gebaeude->codex) . ' wurde eingetragen.');
        $this->erledigtModalSchliessen();
    }

    // ═══════════════════════════════════════════════════════════
    // ✏️ BEARBEITEN MODAL
    // ═══════════════════════════════════════════════════════════
    
    public function bearbeitenModalOeffnen(int $gebaeudeId)
    {
        $this->bearbeitenGebaeudeId = $gebaeudeId;
        $this->bearbeitenGebaeude = Gebaeude::with('touren')->findOrFail($gebaeudeId);
        
        $g = $this->bearbeitenGebaeude;
        
        $this->codex = $g->codex ?? '';
        $this->gebaeude_name = $g->gebaeude_name ?? '';
        $this->strasse = $g->strasse ?? '';
        $this->hausnummer = $g->hausnummer ?? '';
        $this->plz = $g->plz ?? '';
        $this->wohnort = $g->wohnort ?? '';
        $this->land = $g->land ?? 'IT';
        $this->telefon = $g->telefon ?? '';
        $this->handy = $g->handy ?? '';
        $this->email = $g->email ?? '';
        $this->bemerkung = $g->bemerkung ?? '';
        $this->bemerkung_mitarbeiter = '';
        $this->geplante_reinigungen = $g->geplante_reinigungen ?? 0;
        
        $this->m01 = (bool) $g->m01;
        $this->m02 = (bool) $g->m02;
        $this->m03 = (bool) $g->m03;
        $this->m04 = (bool) $g->m04;
        $this->m05 = (bool) $g->m05;
        $this->m06 = (bool) $g->m06;
        $this->m07 = (bool) $g->m07;
        $this->m08 = (bool) $g->m08;
        $this->m09 = (bool) $g->m09;
        $this->m10 = (bool) $g->m10;
        $this->m11 = (bool) $g->m11;
        $this->m12 = (bool) $g->m12;
        
        $this->selectedTouren = $g->touren->pluck('id')->toArray();
        
        $this->showBearbeitenModal = true;
    }

    public function bearbeitenModalSchliessen()
    {
        $this->showBearbeitenModal = false;
        $this->reset([
            'bearbeitenGebaeudeId', 'bearbeitenGebaeude',
            'codex', 'gebaeude_name', 'strasse', 'hausnummer', 'plz', 'wohnort', 'land',
            'telefon', 'handy', 'email', 'bemerkung', 'bemerkung_mitarbeiter', 'geplante_reinigungen',
            'm01', 'm02', 'm03', 'm04', 'm05', 'm06', 'm07', 'm08', 'm09', 'm10', 'm11', 'm12',
            'selectedTouren'
        ]);
    }

    public function aenderungVorschlagen()
    {
        $this->validate([
            'codex' => 'required|string|max:50',
            'strasse' => 'required|string|max:255',
            'hausnummer' => 'required|string|max:20',
            'plz' => 'required|string|max:10',
            'wohnort' => 'required|string|max:255',
            'land' => 'required|string|max:2',
            'email' => 'nullable|email|max:255',
        ]);

        $gebaeude = Gebaeude::findOrFail($this->bearbeitenGebaeudeId);

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
            'bemerkung' => $gebaeude->bemerkung,
            'geplante_reinigungen' => $gebaeude->geplante_reinigungen,
            'm01' => $gebaeude->m01, 'm02' => $gebaeude->m02, 'm03' => $gebaeude->m03,
            'm04' => $gebaeude->m04, 'm05' => $gebaeude->m05, 'm06' => $gebaeude->m06,
            'm07' => $gebaeude->m07, 'm08' => $gebaeude->m08, 'm09' => $gebaeude->m09,
            'm10' => $gebaeude->m10, 'm11' => $gebaeude->m11, 'm12' => $gebaeude->m12,
            'touren' => $gebaeude->touren->pluck('id')->toArray(),
        ];

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
            'bemerkung' => $this->bemerkung,
            'geplante_reinigungen' => $this->geplante_reinigungen,
            'm01' => $this->m01, 'm02' => $this->m02, 'm03' => $this->m03,
            'm04' => $this->m04, 'm05' => $this->m05, 'm06' => $this->m06,
            'm07' => $this->m07, 'm08' => $this->m08, 'm09' => $this->m09,
            'm10' => $this->m10, 'm11' => $this->m11, 'm12' => $this->m12,
            'touren' => $this->selectedTouren,
        ];

        GebaeudeAenderungsvorschlag::create([
            'gebaeude_id' => $gebaeude->id,
            'user_id' => auth()->id(),
            'typ' => 'aenderung',
            'status' => 'pending',
            'alte_daten' => $alteDaten,
            'neue_daten' => $neueDaten,
            'bemerkung' => $this->bemerkung_mitarbeiter,
        ]);

        session()->flash('success', 'Änderungsvorschlag für ' . ($gebaeude->gebaeude_name ?: $gebaeude->codex) . ' wurde eingereicht.');
        $this->bearbeitenModalSchliessen();
    }

    // ═══════════════════════════════════════════════════════════
    // 🛠️ HELPER
    // ═══════════════════════════════════════════════════════════
    
    private function getMonateArray(): array
    {
        return [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
        ];
    }
}
