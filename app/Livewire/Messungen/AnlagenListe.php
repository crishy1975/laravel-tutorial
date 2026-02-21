<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Impianto;

#[Layout('layouts.app')]
class AnlagenListe extends Component
{
    use WithPagination;

    // Filter
    public $filterKodex = '';
    public $filterBeschreibung = '';   // sucht in Feld_w (Name Aufstellungsort)
    public $filterStrasse = '';        // sucht in Feld_m/Feld_l (Straße Aufstellungsort DE/IT)
    public $filterOrt = '';            // sucht in Feld_i/Feld_h (Gemeinde Aufstellungsort DE/IT)
    public $filterHersteller = '';     // sucht in Feld_y (Hersteller Kessel)
    public $filterGemessen = '';
    public $filterJahr;

    // Sortierung
    public $sortField = 'Feld_i';
    public $sortDirection = 'asc';

    protected $queryString = [
        'filterKodex' => ['except' => ''],
        'filterBeschreibung' => ['except' => ''],
        'filterStrasse' => ['except' => ''],
        'filterOrt' => ['except' => ''],
        'filterHersteller' => ['except' => ''],
        'filterGemessen' => ['except' => ''],
        'filterJahr' => ['except' => ''],
    ];

    public function mount()
    {
        $this->filterJahr = date('Y');
    }

    public function updatingFilterKodex()       { $this->resetPage(); }
    public function updatingFilterBeschreibung() { $this->resetPage(); }
    public function updatingFilterStrasse()      { $this->resetPage(); }
    public function updatingFilterOrt()          { $this->resetPage(); }
    public function updatingFilterHersteller()   { $this->resetPage(); }
    public function updatingFilterGemessen()     { $this->resetPage(); }

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
        $this->filterBeschreibung = '';
        $this->filterStrasse = '';
        $this->filterOrt = '';
        $this->filterHersteller = '';
        $this->filterGemessen = '';
        $this->filterJahr = date('Y');
        $this->resetPage();
    }

    public function getAnlagenProperty()
    {
        $query = Impianto::query();

        // Kodex
        if ($this->filterKodex) {
            $query->where('Feld_a', 'like', "%{$this->filterKodex}%");
        }

        // Name Aufstellungsort (Feld_w)
        if ($this->filterBeschreibung) {
            $query->where('Feld_w', 'like', "%{$this->filterBeschreibung}%");
        }

        // Straße Aufstellungsort: DE (Feld_m) oder IT (Feld_l)
        if ($this->filterStrasse) {
            $query->where(function ($q) {
                $q->where('Feld_m', 'like', "%{$this->filterStrasse}%")
                  ->orWhere('Feld_l', 'like', "%{$this->filterStrasse}%");
            });
        }

        // Gemeinde Aufstellungsort: DE (Feld_i) oder IT (Feld_h)
        if ($this->filterOrt) {
            $query->where(function ($q) {
                $q->where('Feld_i', 'like', "%{$this->filterOrt}%")
                  ->orWhere('Feld_h', 'like', "%{$this->filterOrt}%");
            });
        }

        // Hersteller Kessel (Feld_y)
        if ($this->filterHersteller) {
            $query->where('Feld_y', 'like', "%{$this->filterHersteller}%");
        }

        // Messung ja/nein
        if ($this->filterGemessen === '1') {
            $query->mitMessungImJahr((int) $this->filterJahr);
        } elseif ($this->filterGemessen === '0') {
            $query->ohneMessungImJahr((int) $this->filterJahr);
        }

        // Sortierung
        $query->orderBy($this->sortField, $this->sortDirection);

        // Sekundäre Sortierung nach Straße + Hausnummer
        if ($this->sortField !== 'Feld_m') {
            $query->orderBy('Feld_m', 'asc');
        }
        if ($this->sortField !== 'Feld_n') {
            $query->orderBy('Feld_n', 'asc');
        }

        return $query->paginate(25);
    }

    public function getStatistikProperty()
    {
        $jahr = (int) $this->filterJahr;

        return [
            'total' => Impianto::count(),
            'mitMessung' => Impianto::mitMessungImJahr($jahr)->count(),
            'ohneMessung' => Impianto::ohneMessungImJahr($jahr)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.messungen.anlagen-liste', [
            'anlagen' => $this->anlagen,
            'statistik' => $this->statistik,
        ]);
    }
}
