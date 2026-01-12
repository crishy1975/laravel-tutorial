<?php
// app/Livewire/Mitarbeiter/Reinigungsplanung.php

namespace App\Livewire\Mitarbeiter;

use App\Models\Gebaeude;
use App\Models\Tour;
use App\Models\GebaeudeAenderungsvorschlag;
use App\Services\FaelligkeitsService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.mitarbeiter')]
class Reinigungsplanung extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filter
    public $filterTour = '';
    public $filterMonat = '';
    public $filterStatus = '';
    public $filterCodex = '';
    public $suchbegriff = '';

    // Erledigt Modal
    public $showErledigtModal = false;
    public $erledigtGebaeudeId = null;
    public $erledigtDatum;
    public $erledigtBemerkung = '';

    // Bearbeiten Modal
    public $showBearbeitenModal = false;
    public $bearbeitenGebaeudeId = null;
    public $bearbeitenGebaeudeCodex = '';
    public $bearbeitenGebaeudeName = '';
    
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
    
    public $selectedTouren = [];

    public function mount()
    {
        // Filter aus Session laden
        $this->filterTour = session('reinigung_filter_tour', '');
        $this->filterMonat = session('reinigung_filter_monat', now()->month);
        $this->filterStatus = session('reinigung_filter_status', '');
        $this->filterCodex = session('reinigung_filter_codex', '');
        $this->suchbegriff = session('reinigung_filter_suche', '');
        
        $this->erledigtDatum = today()->format('Y-m-d');
    }

    #[Computed]
    public function alleTouren()
    {
        return Tour::where('aktiv', true)->orderBy('name')->get();
    }

    // Filter in Session speichern bei Änderung
    public function updatedFilterTour($value)
    {
        session(['reinigung_filter_tour' => $value]);
        $this->resetPage();
    }

    public function updatedFilterMonat($value)
    {
        session(['reinigung_filter_monat' => $value]);
        $this->resetPage();
    }

    public function updatedFilterStatus($value)
    {
        session(['reinigung_filter_status' => $value]);
        $this->resetPage();
    }

    public function updatedFilterCodex($value)
    {
        session(['reinigung_filter_codex' => $value]);
        $this->resetPage();
    }

    public function updatedSuchbegriff($value)
    {
        session(['reinigung_filter_suche' => $value]);
        $this->resetPage();
    }

    public function render()
    {
        // Modal offen? Keine schwere Berechnung!
        if ($this->showBearbeitenModal || $this->showErledigtModal) {
            return view('livewire.mitarbeiter.reinigungsplanung', [
                'gebaeude' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'touren' => $this->alleTouren,
                'stats' => ['gesamt' => '-', 'offen' => '-', 'erledigt' => '-'],
                'monate' => $this->getMonateArray(),
                'skipList' => true,
            ]);
        }

        $faelligkeitsService = app(FaelligkeitsService::class);

        // Query aufbauen - OHNE ist_faellig (das ist keine DB-Spalte!)
        $query = Gebaeude::query()->with(['touren']);

        // Filter: Monat
        if (!empty($this->filterMonat) && $this->filterMonat >= 1 && $this->filterMonat <= 12) {
            $monatFeld = 'm' . str_pad($this->filterMonat, 2, '0', STR_PAD_LEFT);
            $query->where($monatFeld, true);
        }

        // Filter: Tour
        if (!empty($this->filterTour)) {
            $query->whereHas('touren', fn($q) => $q->where('tour.id', $this->filterTour));
        }

        // Filter: Codex
        if (!empty($this->filterCodex)) {
            $query->where('codex', 'LIKE', '%' . $this->filterCodex . '%');
        }

        // Filter: Suchbegriff (Name, Straße, Ort)
        if (!empty($this->suchbegriff)) {
            $query->where(function($q) {
                $q->where('gebaeude_name', 'LIKE', '%' . $this->suchbegriff . '%')
                  ->orWhere('strasse', 'LIKE', '%' . $this->suchbegriff . '%')
                  ->orWhere('wohnort', 'LIKE', '%' . $this->suchbegriff . '%');
            });
        }

        // Zählung für Stats (ohne Status-Filter)
        $totalCount = $query->count();

        // Sortierung & Pagination
        $gebaeudePaginated = $query
            ->orderBy('strasse')
            ->orderByRaw('CAST(hausnummer AS UNSIGNED)')
            ->orderBy('hausnummer')
            ->paginate(20);

        // Fälligkeit NUR für aktuelle Seite berechnen
        $statsOffen = 0;
        $statsErledigt = 0;

        foreach ($gebaeudePaginated->items() as $g) {
            $g->letzte_reinigung_datum = $faelligkeitsService->getLetzteReinigung($g);
            $g->naechste_faelligkeit = $faelligkeitsService->getNaechsteFaelligkeit($g);
            $g->ist_erledigt = !$faelligkeitsService->istFaellig($g);
            
            $g->ist_erledigt ? $statsErledigt++ : $statsOffen++;
        }

        // Status-Filter NUR auf Collection anwenden (NICHT auf DB!)
        $items = collect($gebaeudePaginated->items());
        if ($this->filterStatus === 'offen') {
            $items = $items->filter(fn($g) => !$g->ist_erledigt);
        } elseif ($this->filterStatus === 'erledigt') {
            $items = $items->filter(fn($g) => $g->ist_erledigt);
        }

        return view('livewire.mitarbeiter.reinigungsplanung', [
            'gebaeude' => $this->filterStatus 
                ? new \Illuminate\Pagination\LengthAwarePaginator($items->values(), $items->count(), 20, 1, ['path' => request()->url()])
                : $gebaeudePaginated,
            'touren' => $this->alleTouren,
            'stats' => [
                'gesamt'   => $totalCount,
                'offen'    => $statsOffen,
                'erledigt' => $statsErledigt,
            ],
            'monate' => $this->getMonateArray(),
            'skipList' => false,
        ]);
    }

    public function filterZuruecksetzen()
    {
        $this->filterTour = '';
        $this->filterMonat = now()->month;
        $this->filterStatus = '';
        $this->filterCodex = '';
        $this->suchbegriff = '';
        
        // Session leeren
        session()->forget([
            'reinigung_filter_tour',
            'reinigung_filter_monat', 
            'reinigung_filter_status',
            'reinigung_filter_codex',
            'reinigung_filter_suche'
        ]);
        session(['reinigung_filter_monat' => now()->month]);
        
        $this->resetPage();
    }

    // Erledigt Modal
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

        app(FaelligkeitsService::class)->aktualisiereGebaeude($gebaeude);

        session()->flash('success', 'Reinigung für ' . ($gebaeude->gebaeude_name ?: $gebaeude->codex) . ' eingetragen.');
        $this->erledigtModalSchliessen();
    }

    // Bearbeiten Modal
    public function bearbeitenModalOeffnen(int $gebaeudeId)
    {
        $g = Gebaeude::with('touren')->findOrFail($gebaeudeId);
        
        $this->bearbeitenGebaeudeId = $gebaeudeId;
        $this->bearbeitenGebaeudeCodex = $g->codex ?? '';
        $this->bearbeitenGebaeudeName = $g->gebaeude_name ?? '';
        
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
            'bearbeitenGebaeudeId', 'bearbeitenGebaeudeCodex', 'bearbeitenGebaeudeName',
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

        $gebaeude = Gebaeude::with('touren')->findOrFail($this->bearbeitenGebaeudeId);

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

        session()->flash('success', 'Änderungsvorschlag für ' . ($gebaeude->gebaeude_name ?: $gebaeude->codex) . ' eingereicht.');
        $this->bearbeitenModalSchliessen();
    }

    private function getMonateArray(): array
    {
        return [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
        ];
    }
}
