<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Messung;
use App\Models\Impianto;

#[Layout('layouts.app')]
class MessungenListe extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filter
    public $filterKodex = '';
    public $filterName = '';
    public $filterDatumVon = '';
    public $filterDatumBis = '';
    public $filterErgebnis = '';
    public $filterBrennstoff = '';
    public $filterOhneAnlage = '';
    public $filterJahr;

    // Sortierung
    public $sortField = 'cMIS_DATA';
    public $sortDirection = 'desc';

    // Modal: Anlage zuordnen
    public $showAnlageModal = false;
    public $selectedMessungId = null;
    public $selectedMessung = null;
    public $anlageSearchOrt = '';
    public $anlageSearchStrasse = '';
    public $anlageSearchNummer = '';
    public $anlageSearchName = '';
    public $anlageSearchResults = [];

    protected $queryString = [
        'filterKodex' => ['except' => ''],
        'filterName' => ['except' => ''],
        'filterDatumVon' => ['except' => ''],
        'filterDatumBis' => ['except' => ''],
        'filterErgebnis' => ['except' => ''],
        'filterBrennstoff' => ['except' => ''],
        'filterOhneAnlage' => ['except' => ''],
        'filterJahr' => ['except' => ''],
    ];

    public function mount()
    {
        $this->filterJahr = date('Y');
    }

    public function updatingFilterKodex()     { $this->resetPage(); }
    public function updatingFilterName()      { $this->resetPage(); }
    public function updatingFilterDatumVon()  { $this->resetPage(); }
    public function updatingFilterDatumBis()  { $this->resetPage(); }
    public function updatingFilterErgebnis()  { $this->resetPage(); }
    public function updatingFilterBrennstoff(){ $this->resetPage(); }
    public function updatingFilterOhneAnlage(){ $this->resetPage(); }
    public function updatingFilterJahr()      { $this->resetPage(); }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->filterKodex = '';
        $this->filterName = '';
        $this->filterDatumVon = '';
        $this->filterDatumBis = '';
        $this->filterErgebnis = '';
        $this->filterBrennstoff = '';
        $this->filterOhneAnlage = '';
        $this->filterJahr = date('Y');
        $this->resetPage();
    }

    // ========== Modal: Anlage zuordnen ==========
    
    public function openAnlageModal($messungId)
    {
        $this->selectedMessungId = $messungId;
        $this->selectedMessung = Messung::find($messungId);
        $this->resetAnlageSearch();
        $this->showAnlageModal = true;
    }

    public function closeAnlageModal()
    {
        $this->showAnlageModal = false;
        $this->selectedMessungId = null;
        $this->selectedMessung = null;
        $this->resetAnlageSearch();
    }

    public function resetAnlageSearch()
    {
        $this->anlageSearchOrt = '';
        $this->anlageSearchStrasse = '';
        $this->anlageSearchNummer = '';
        $this->anlageSearchName = '';
        $this->anlageSearchResults = [];
    }

    public function searchAnlagen()
    {
        $query = Impianto::query();

        // Mindestens ein Suchkriterium erforderlich
        $hasFilter = false;

        // Aufstellungsort/Name (Feld_w)
        if ($this->anlageSearchName) {
            $query->where('Feld_w', 'like', "%{$this->anlageSearchName}%");
            $hasFilter = true;
        }

        // Gemeinde/Ort (Feld_i = DE, Feld_h = IT)
        if ($this->anlageSearchOrt) {
            $query->where(function ($q) {
                $q->where('Feld_i', 'like', "%{$this->anlageSearchOrt}%")
                  ->orWhere('Feld_h', 'like', "%{$this->anlageSearchOrt}%");
            });
            $hasFilter = true;
        }

        // Straße (Feld_m = DE, Feld_l = IT)
        if ($this->anlageSearchStrasse) {
            $query->where(function ($q) {
                $q->where('Feld_m', 'like', "%{$this->anlageSearchStrasse}%")
                  ->orWhere('Feld_l', 'like', "%{$this->anlageSearchStrasse}%");
            });
            $hasFilter = true;
        }

        // Hausnummer (Feld_n)
        if ($this->anlageSearchNummer) {
            $query->where('Feld_n', 'like', "%{$this->anlageSearchNummer}%");
            $hasFilter = true;
        }

        if (!$hasFilter) {
            $this->anlageSearchResults = [];
            return;
        }

        // Sortierung: Gemeinde, Straße, Hausnummer (numerisch!)
        $query->orderBy('Feld_i', 'asc')
              ->orderBy('Feld_m', 'asc')
              ->orderByRaw('CAST(Feld_n AS UNSIGNED) ASC')
              ->orderBy('Feld_n', 'asc'); // Fallback für nicht-numerische

        $this->anlageSearchResults = $query->limit(20)->get();
    }

    public function zuordnenAnlage($anlageKodex)
    {
        if (!$this->selectedMessungId) {
            return;
        }

        $messung = Messung::find($this->selectedMessungId);
        if (!$messung) {
            return;
        }

        // Anlage laden für zusätzliche Infos
        $anlage = Impianto::where('Feld_a', $anlageKodex)->first();

        // Messung aktualisieren
        $messung->cIM_CODICE = $anlageKodex;
        $messung->codeInImpianti = 1;
        
        // Optional: Baujahr und Leistung von Anlage übernehmen wenn leer
        if (empty($messung->boilerYear) && $anlage && $anlage->Feld_z) {
            $messung->boilerYear = $anlage->Feld_z;
        }
        if (empty($messung->boilerPower) && $anlage && $anlage->Feld_ab) {
            $messung->boilerPower = $anlage->Feld_ab;
        }

        $messung->save();

        $this->closeAnlageModal();
        
        session()->flash('success', "Messung wurde der Anlage {$anlageKodex} zugeordnet.");
    }

    // ========== Properties ==========

    public function getMessungenProperty()
    {
        $query = Messung::query();

        // Jahr-Filter (Standard)
        if ($this->filterJahr) {
            $query->imJahr((int) $this->filterJahr);
        }

        // Kodex
        if ($this->filterKodex) {
            $query->where('cIM_CODICE', 'like', "%{$this->filterKodex}%");
        }

        // Name/Kunde
        if ($this->filterName) {
            $query->where('cIM_NAME', 'like', "%{$this->filterName}%");
        }

        // Datum von
        if ($this->filterDatumVon) {
            $datumVon = date('dmY', strtotime($this->filterDatumVon));
            $query->where('cMIS_DATA', '>=', $datumVon);
        }

        // Datum bis
        if ($this->filterDatumBis) {
            $datumBis = date('dmY', strtotime($this->filterDatumBis));
            $query->where('cMIS_DATA', '<=', $datumBis);
        }

        // Ergebnis
        if ($this->filterErgebnis !== '') {
            $query->where('strEsito', $this->filterErgebnis);
        }

        // Brennstoff
        if ($this->filterBrennstoff) {
            $query->where('cMIS_COMBUSTIBILE', $this->filterBrennstoff);
        }

        // Ohne Anlage
        if ($this->filterOhneAnlage === '1') {
            $query->where('codeInImpianti', 0);
        } elseif ($this->filterOhneAnlage === '0') {
            $query->where('codeInImpianti', '>', 0);
        }

        // Sortierung
        $query->orderBy($this->sortField, $this->sortDirection);

        // Sekundäre Sortierung: Stadio aufwärts
        if ($this->sortField !== 'cMIS_STADIO') {
            $query->orderBy('cMIS_STADIO', 'asc');
        }
        
        // Tertiäre Sortierung: Uhrzeit
        if ($this->sortField !== 'cMIS_ORA') {
            $query->orderBy('cMIS_ORA', 'desc');
        }

        return $query->paginate(25);
    }

    public function getStatistikProperty()
    {
        $jahr = (int) $this->filterJahr;

        $query = Messung::query();
        if ($jahr) {
            $query->imJahr($jahr);
        }

        $total = (clone $query)->count();
        $positiv = (clone $query)->positiv()->count();
        $negativ = (clone $query)->negativ()->count();
        $ohneAnlage = (clone $query)->where('codeInImpianti', 0)->count();

        return [
            'total' => $total,
            'positiv' => $positiv,
            'negativ' => $negativ,
            'ohneAnlage' => $ohneAnlage,
        ];
    }

    public function getBrennstoffeProperty()
    {
        return Messung::BRENNSTOFFE;
    }

    public function delete($id)
    {
        $messung = Messung::findOrFail($id);
        $messung->delete();
        
        session()->flash('success', 'Messung wurde gelöscht.');
    }

    public function render()
    {
        return view('livewire.messungen.messungen-liste', [
            'messungen' => $this->messungen,
            'statistik' => $this->statistik,
            'brennstoffe' => $this->brennstoffe,
        ]);
    }
}
