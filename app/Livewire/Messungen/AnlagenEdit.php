<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Impianto;

#[Layout('layouts.app')]
class AnlagenEdit extends Component
{
    public Impianto $anlage;

    // Identifikation
    public $Feld_a, $Feld_b, $Feld_c;

    // Aufstellungsort
    public $Feld_h, $Feld_i;       // Gemeinde IT/DE
    public $Feld_J, $Feld_K;       // Fraktion IT/DE (Großbuchstabe!)
    public $Feld_l, $Feld_m;       // Straße IT/DE
    public $Feld_n;                 // Hausnummer
    public $Feld_w;                 // Name Aufstellungsort

    // Betreiber
    public $Feld_o;                 // Name Betreiber
    public $Feld_p, $Feld_q;       // Gemeinde IT/DE
    public $Feld_r, $Feld_s;       // Fraktion IT/DE
    public $Feld_t, $Feld_u;       // Straße IT/DE
    public $Feld_v;                 // Hausnummer

    // Kessel
    public $Feld_x;                 // Status
    public $Feld_y;                 // Hersteller
    public $Feld_z;                 // Baujahr
    public $Feld_ab;                // Leistung kW

    public $saved = false;

    protected $rules = [
        'Feld_a' => 'required|string|max:10',
        'Feld_w' => 'nullable|string|max:255',
        'Feld_y' => 'nullable|string|max:100',
        'Feld_z' => 'nullable|string|max:4',
        'Feld_ab' => 'nullable|string|max:10',
    ];

    public function mount($kodex)
    {
        $this->anlage = Impianto::where('Feld_a', $kodex)->firstOrFail();

        foreach ($this->anlage->getFillable() as $field) {
            if (property_exists($this, $field)) {
                $this->$field = $this->anlage->$field;
            }
        }
    }

    public function save()
    {
        $this->validate();

        $updateData = [];
        foreach ($this->anlage->getFillable() as $field) {
            if ($field === 'Feld_a') continue; // PK nicht ändern
            if (property_exists($this, $field)) {
                $updateData[$field] = $this->$field;
            }
        }

        $this->anlage->update($updateData);
        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.messungen.anlagen-edit');
    }
}
