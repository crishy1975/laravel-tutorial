<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Impianto;

#[Layout('layouts.app')]
class AnlagenEdit extends Component
{
    public Impianto $anlage;
    
    public $Feld_a, $Feld_b, $Feld_c;
    public $Feld_h, $Feld_i, $Feld_j, $Feld_k;
    public $Feld_l, $Feld_m, $Feld_n;
    public $Feld_o, $Feld_p, $Feld_q, $Feld_r, $Feld_s, $Feld_t;
    public $Feld_u, $Feld_v, $Feld_w;
    public $Feld_x, $Feld_y, $Feld_z, $Feld_ab;

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

        $this->anlage->update([
            'Feld_b' => $this->Feld_b,
            'Feld_c' => $this->Feld_c,
            'Feld_h' => $this->Feld_h,
            'Feld_i' => $this->Feld_i,
            'Feld_j' => $this->Feld_j,
            'Feld_k' => $this->Feld_k,
            'Feld_l' => $this->Feld_l,
            'Feld_m' => $this->Feld_m,
            'Feld_n' => $this->Feld_n,
            'Feld_o' => $this->Feld_o,
            'Feld_p' => $this->Feld_p,
            'Feld_q' => $this->Feld_q,
            'Feld_r' => $this->Feld_r,
            'Feld_s' => $this->Feld_s,
            'Feld_t' => $this->Feld_t,
            'Feld_u' => $this->Feld_u,
            'Feld_v' => $this->Feld_v,
            'Feld_w' => $this->Feld_w,
            'Feld_x' => $this->Feld_x,
            'Feld_y' => $this->Feld_y,
            'Feld_z' => $this->Feld_z,
            'Feld_ab' => $this->Feld_ab,
        ]);

        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.messungen.anlagen-edit');
    }
}
