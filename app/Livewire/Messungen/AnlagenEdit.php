<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Impianto;
use Illuminate\Support\Facades\Log;

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
                // FIX: null → '' damit Livewire nicht mit null-Werten
                // Probleme bei wire:model bekommt
                $this->$field = $this->anlage->$field ?? '';
            }
        }
    }

    public function save()
    {
        $this->validate();
        $this->saved = false;

        try {
            // FIX: Bei jedem Save frisch aus DB laden um
            // Livewire-Serialisierungs-Probleme zu vermeiden
            $anlage = Impianto::where('Feld_a', $this->Feld_a)->firstOrFail();

            $updateData = [];
            foreach ($anlage->getFillable() as $field) {
                if ($field === 'Feld_a') continue; // PK nicht ändern
                if (property_exists($this, $field)) {
                    // FIX: leer-String zu null konvertieren falls DB-Spalte nullable
                    // (verhindert Probleme mit STRICT_TRANS_TABLES bei Hostinger)
                    $value = $this->$field;
                    $updateData[$field] = ($value === '') ? null : $value;
                }
            }

            // Debug-Log: Was wird tatsächlich geschrieben?
            Log::info('Anlage vor update()', [
                'kodex' => $this->Feld_a,
                'update_data_keys' => array_keys($updateData),
            ]);

            $updated = $anlage->update($updateData);

            if (!$updated) {
                Log::error('Anlage::update() gab false zurück', [
                    'kodex' => $this->Feld_a,
                    'dirty' => $anlage->getDirty(),
                ]);
                session()->flash('error', 'Speichern fehlgeschlagen (update() returned false).');
                return;
            }

            // Nach erfolgreichem Update: frisch laden zur Verifikation
            $check = Impianto::where('Feld_a', $this->Feld_a)->first();
            if (!$check) {
                Log::error('Anlage nach update() nicht auffindbar', [
                    'kodex' => $this->Feld_a,
                ]);
                session()->flash('error', 'Anlage wurde nicht persistiert.');
                return;
            }

            // Lokale Referenz aktualisieren damit die Seite konsistent bleibt
            $this->anlage = $check;

            Log::info('Anlage erfolgreich aktualisiert', [
                'kodex' => $this->Feld_a,
            ]);

            $this->saved = true;

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('DB-Fehler beim Anlage speichern', [
                'kodex' => $this->Feld_a,
                'sql_state' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
            session()->flash('error', 'Datenbankfehler: ' . $e->getMessage());
            return;

        } catch (\Exception $e) {
            Log::error('Unerwarteter Fehler beim Anlage speichern', [
                'kodex' => $this->Feld_a,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Fehler: ' . $e->getMessage());
            return;
        }
    }

    public function render()
    {
        return view('livewire.messungen.anlagen-edit');
    }
}
