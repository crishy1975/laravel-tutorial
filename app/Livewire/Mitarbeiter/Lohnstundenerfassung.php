<?php

namespace App\Livewire\Mitarbeiter;

use App\Models\Lohnstunde;
use Livewire\Component;
use Livewire\WithPagination;

class Lohnstundenerfassung extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Formular-Felder
    public $datum;
    public $typ = 'No';
    public $stunden;
    public $notizen;

    // Bearbeitungsmodus
    public $editId = null;
    public $showForm = false;

    // Filter
    public $filterMonat;
    public $filterJahr;

    protected $rules = [
        'datum' => 'required|date',
        'typ' => 'required|string|max:10',
        'stunden' => 'required|numeric|min:0.25|max:24',
        'notizen' => 'nullable|string|max:1000',
    ];

    protected $messages = [
        'datum.required' => 'Bitte Datum eingeben / Inserire la data',
        'stunden.required' => 'Bitte Stunden eingeben / Inserire le ore',
        'stunden.min' => 'Mindestens 0.25 Stunden / Minimo 0,25 ore',
        'stunden.max' => 'Maximal 24 Stunden / Massimo 24 ore',
    ];

    public function mount()
    {
        $this->datum = today()->format('Y-m-d');
        $this->filterMonat = now()->month;
        $this->filterJahr = now()->year;
    }

    public function render()
    {
        $eintraege = Lohnstunde::where('user_id', auth()->id())
            ->monat($this->filterMonat, $this->filterJahr)
            ->orderBy('datum', 'desc')
            ->paginate(15);

        $gesamtStunden = Lohnstunde::where('user_id', auth()->id())
            ->monat($this->filterMonat, $this->filterJahr)
            ->sum('stunden');

        // Stunden nach Typ gruppiert
        $stundenNachTyp = Lohnstunde::where('user_id', auth()->id())
            ->monat($this->filterMonat, $this->filterJahr)
            ->selectRaw('typ, SUM(stunden) as summe')
            ->groupBy('typ')
            ->pluck('summe', 'typ')
            ->toArray();

        return view('livewire.mitarbeiter.lohnstundenerfassung', [
            'eintraege' => $eintraege,
            'gesamtStunden' => $gesamtStunden,
            'stundenNachTyp' => $stundenNachTyp,
            'typen' => Lohnstunde::getTypen(),
        ])->layout('layouts.mitarbeiter');
    }

    public function neu()
    {
        $this->reset(['editId', 'stunden', 'notizen']);
        $this->typ = 'No';
        $this->datum = today()->format('Y-m-d');
        $this->showForm = true;
    }

    public function speichern()
    {
        $this->validate();

        $data = [
            'user_id' => auth()->id(),
            'datum' => $this->datum,
            'typ' => $this->typ,
            'stunden' => $this->stunden,
            'notizen' => $this->notizen,
        ];

        if ($this->editId) {
            $eintrag = Lohnstunde::where('id', $this->editId)
                ->where('user_id', auth()->id())
                ->firstOrFail();
            $eintrag->update($data);
            session()->flash('success', 'Eintrag aktualisiert / Voce aggiornata');
        } else {
            Lohnstunde::create($data);
            session()->flash('success', 'Stunden erfasst / Ore registrate');
        }

        $this->reset(['editId', 'stunden', 'notizen', 'showForm']);
        $this->typ = 'No';
        $this->datum = today()->format('Y-m-d');
    }

    public function bearbeiten($id)
    {
        $eintrag = Lohnstunde::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->editId = $eintrag->id;
        $this->datum = $eintrag->datum->format('Y-m-d');
        $this->typ = $eintrag->typ;
        $this->stunden = $eintrag->stunden;
        $this->notizen = $eintrag->notizen;
        $this->showForm = true;
    }

    public function loeschen($id)
    {
        Lohnstunde::where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();

        session()->flash('success', 'Eintrag gelöscht / Voce eliminata');
    }

    public function abbrechen()
    {
        $this->reset(['editId', 'stunden', 'notizen', 'showForm']);
        $this->typ = 'No';
        $this->datum = today()->format('Y-m-d');
    }

    public function updatedFilterMonat()
    {
        $this->resetPage();
    }

    public function updatedFilterJahr()
    {
        $this->resetPage();
    }
}
