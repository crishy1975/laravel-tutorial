<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Messung;
use App\Models\Impianto;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
class MessungEdit extends Component
{
    public ?Messung $messung = null;
    public bool $isNew = false;
    public ?string $kodex = null;

    // Formular-Felder
    public $cIM_CODICE = '';
    public $cIM_NAME = '';
    public $cMIS_TIPO = '001';
    public $cMIS_STADIO = '1';
    public $datum = '';
    public $uhrzeit = '';
    public $cMIS_COMBUSTIBILE = 'FUEL_NAT_GAS';
    public $cMIS_T_GAS_COMB = '';
    public $cMIS_T_ARIA_COMB = '';
    public $cMIS_T_LIQ_CONV = '060';
    public $cMIS_OSSIGENO = '';
    public $cMIS_ANIDRIDE_CARBONICA = '';
    public $cMIS_MONOSSSIDO = '';
    public $cMIS_BIOSSIDO_AZOTO = '';
    public $cMIS_PERD_FUMI = '';
    public $cMIS_IND_OPACITA = '0';
    public $cMIS_TRACCE_OLEO = '1';
    public $strEsito = '';
    public $boilerYear = '';
    public $boilerPower = '';

    // Berechnete Werte
    public $grenzwertDetails = [];
    public $anlageInfo = null;

    protected $rules = [
        'cIM_CODICE' => 'required|string|max:16',
        'cIM_NAME' => 'nullable|string|max:255',
        'cMIS_TIPO' => 'required|string|max:3',
        'cMIS_STADIO' => 'required|string|max:2',
        'datum' => 'required|date',
        'uhrzeit' => 'required',
        'cMIS_COMBUSTIBILE' => 'required|string',
        'cMIS_T_GAS_COMB' => 'nullable|numeric|min:0|max:999',
        'cMIS_T_ARIA_COMB' => 'nullable|numeric|min:-20|max:99',
        'cMIS_T_LIQ_CONV' => 'nullable|numeric|min:0|max:999',
        'cMIS_OSSIGENO' => 'nullable|numeric|min:0|max:21',
        'cMIS_ANIDRIDE_CARBONICA' => 'nullable|numeric|min:0|max:20',
        'cMIS_MONOSSSIDO' => 'nullable|integer|min:0|max:999999',
        'cMIS_BIOSSIDO_AZOTO' => 'nullable|integer|min:0|max:9999',
        'cMIS_PERD_FUMI' => 'nullable|numeric|min:0|max:100',
        'cMIS_IND_OPACITA' => 'nullable|integer|min:0|max:9',
        'cMIS_TRACCE_OLEO' => 'required|in:0,1',
        'boilerYear' => 'nullable|integer|min:1900|max:2100',
        'boilerPower' => 'nullable|integer|min:0|max:9999',
    ];

    public function mount($id = null, $kodex = null)
    {
        if ($id) {
            $this->messung = Messung::findOrFail($id);
            $this->isNew = false;
            $this->fillFromMessung();
        } else {
            $this->isNew = true;
            $this->kodex = $kodex;
            $this->datum = date('Y-m-d');
            $this->uhrzeit = date('H:i:s');

            if ($kodex) {
                $this->cIM_CODICE = $kodex;
                $this->loadAnlageInfo();
            }
        }
    }

    /**
     * FIX: Saubere Unterscheidung zwischen "leer" und "0"
     * Vorher: (int) "00" ?: '' führte zu '' → 0-Werte gingen verloren
     */
    private function fillFromMessung()
    {
        $m = $this->messung;

        $this->cIM_CODICE = $m->cIM_CODICE;
        $this->cIM_NAME = $m->cIM_NAME;
        $this->cMIS_TIPO = $m->cMIS_TIPO;
        $this->cMIS_STADIO = $m->cMIS_STADIO;

        if ($m->cMIS_DATA) {
            $this->datum = \Carbon\Carbon::createFromFormat('dmY', $m->cMIS_DATA)->format('Y-m-d');
        }
        $this->uhrzeit = $m->cMIS_ORA;

        $this->cMIS_COMBUSTIBILE = $m->cMIS_COMBUSTIBILE;

        // Integer-Felder: unterscheidet leer (null/'') von 0
        $this->cMIS_T_GAS_COMB = $this->toIntStringOrEmpty($m->cMIS_T_GAS_COMB);
        $this->cMIS_T_ARIA_COMB = $this->toIntStringOrEmpty($m->cMIS_T_ARIA_COMB);
        $this->cMIS_T_LIQ_CONV = $m->cMIS_T_LIQ_CONV ?: '060';
        $this->cMIS_MONOSSSIDO = $this->toIntStringOrEmpty($m->cMIS_MONOSSSIDO);
        $this->cMIS_BIOSSIDO_AZOTO = $this->toIntStringOrEmpty($m->cMIS_BIOSSIDO_AZOTO);

        // Float-Felder: bleiben Float-Strings
        $this->cMIS_OSSIGENO = $this->toFloatStringOrEmpty($m->cMIS_OSSIGENO);
        $this->cMIS_ANIDRIDE_CARBONICA = $this->toFloatStringOrEmpty($m->cMIS_ANIDRIDE_CARBONICA);
        $this->cMIS_PERD_FUMI = $this->toFloatStringOrEmpty($m->cMIS_PERD_FUMI);

        $this->cMIS_IND_OPACITA = ($m->cMIS_IND_OPACITA !== null && $m->cMIS_IND_OPACITA !== '')
            ? (string) (int) $m->cMIS_IND_OPACITA
            : '0';
        $this->cMIS_TRACCE_OLEO = $m->cMIS_TRACCE_OLEO !== null && $m->cMIS_TRACCE_OLEO !== ''
            ? (string) $m->cMIS_TRACCE_OLEO
            : '1';
        $this->strEsito = $m->strEsito;
        $this->boilerYear = $this->toIntStringOrEmpty($m->boilerYear);
        $this->boilerPower = $this->toIntStringOrEmpty($m->boilerPower);

        $this->loadAnlageInfo();
        $this->berechneGrenzwerte();
    }

    /**
     * Helper: Integer-Wert aus DB zu String, aber '' wenn wirklich leer
     */
    private function toIntStringOrEmpty($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return (string) (int) $value;
    }

    /**
     * Helper: Float-Wert aus DB zu String, aber '' wenn wirklich leer
     */
    private function toFloatStringOrEmpty($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return (string) (float) $value;
    }

    public function updatedCIMCODICE()
    {
        $this->loadAnlageInfo();
    }

    public function loadAnlageInfo()
    {
        if (!$this->cIM_CODICE) {
            $this->anlageInfo = null;
            return;
        }

        $anlage = Impianto::where('Feld_a', $this->cIM_CODICE)->first();

        if ($anlage) {
            $this->anlageInfo = [
                'kodex' => $anlage->Feld_a,
                'name' => $anlage->Feld_w,
                'ort' => $anlage->Feld_i,
                'strasse' => $anlage->Feld_m . ' ' . $anlage->Feld_n,
                'hersteller' => $anlage->Feld_y,
                'baujahr' => $anlage->Feld_z,
                'leistung' => $anlage->Feld_ab,
            ];

            if (!$this->boilerYear && $anlage->Feld_z) {
                $this->boilerYear = (string) $anlage->Feld_z;
            }
            if (!$this->boilerPower && $anlage->Feld_ab) {
                $this->boilerPower = (string) $anlage->Feld_ab;
            }
        } else {
            $this->anlageInfo = null;
        }
    }

    public function berechneGrenzwerte()
    {
        try {
            $temp = new Messung();
            $temp->cMIS_COMBUSTIBILE = $this->cMIS_COMBUSTIBILE;
            $temp->cMIS_MONOSSSIDO = $this->cMIS_MONOSSSIDO;
            $temp->cMIS_BIOSSIDO_AZOTO = $this->cMIS_BIOSSIDO_AZOTO;
            $temp->cMIS_IND_OPACITA = $this->cMIS_IND_OPACITA;
            $temp->cMIS_TRACCE_OLEO = $this->cMIS_TRACCE_OLEO;
            $temp->boilerYear = $this->boilerYear ?: 2000;
            $temp->boilerPower = $this->boilerPower ?: 0;

            $this->grenzwertDetails = $temp->getGrenzwertDetails();
            $this->strEsito = (string) $temp->berechneErgebnis();
        } catch (\Exception $e) {
            Log::warning('Grenzwert-Berechnung fehlgeschlagen', [
                'error' => $e->getMessage(),
                'combustibile' => $this->cMIS_COMBUSTIBILE,
            ]);
            $this->grenzwertDetails = [];
        }
    }

    public function save()
    {
        $this->validate();

        try {
            $this->berechneGrenzwerte();

            // FIX: Bei Edit frisch aus DB laden um Livewire-Serialisierungs-
            // Probleme zu vermeiden
            if ($this->isNew) {
                $messung = new Messung();
            } else {
                $messung = Messung::findOrFail($this->messung->id);
            }

            $brennstoffMapping = Messung::BRENNSTOFFE[$this->cMIS_COMBUSTIBILE]
                ?? ['nr' => 0, 'text' => 'Unbekannt'];

            // Pflichtfelder
            $messung->cIM_CODICE = $this->cIM_CODICE;
            $messung->cIM_NAME = $this->cIM_NAME ?: '';
            $messung->cMIS_TIPO = sprintf('%03d', (int) $this->cMIS_TIPO);
            $messung->cMIS_STADIO = sprintf('%01d', (int) $this->cMIS_STADIO);
            $messung->cMIS_DATA = date('dmY', strtotime($this->datum));
            $messung->cMIS_DATA2 = date('d.m.Y', strtotime($this->datum));
            $messung->cMIS_ORA = $this->uhrzeit;
            $messung->cMIS_COMBUSTIBILE = $this->cMIS_COMBUSTIBILE;
            $messung->cMIS_COMBUSTIBILE_N = $brennstoffMapping['nr'];
            $messung->cMIS_COMBUSTIBILE_P = $brennstoffMapping['text'];

            // FIX: saubere leer/0 Unterscheidung + korrekte Formatierung
            $messung->cMIS_T_GAS_COMB = $this->formatIntField($this->cMIS_T_GAS_COMB, 3);
            $messung->cMIS_T_ARIA_COMB = $this->formatIntField($this->cMIS_T_ARIA_COMB, 2);
            $messung->cMIS_T_LIQ_CONV = $this->cMIS_T_LIQ_CONV ?: '060';
            $messung->cMIS_OSSIGENO = $this->formatFloatField($this->cMIS_OSSIGENO);
            $messung->cMIS_ANIDRIDE_CARBONICA = $this->formatFloatField($this->cMIS_ANIDRIDE_CARBONICA);
            $messung->cMIS_MONOSSSIDO = $this->formatIntField($this->cMIS_MONOSSSIDO, 4);
            $messung->cMIS_BIOSSIDO_AZOTO = $this->formatIntField($this->cMIS_BIOSSIDO_AZOTO, 4);
            $messung->cMIS_PERD_FUMI = $this->formatFloatField($this->cMIS_PERD_FUMI);
            $messung->cMIS_IND_OPACITA = sprintf('%01d', (int) $this->cMIS_IND_OPACITA);
            $messung->cMIS_TRACCE_OLEO = $this->cMIS_TRACCE_OLEO;
            $messung->strEsito = $this->strEsito;
            $messung->boilerYear = $this->boilerYear !== '' ? sprintf('%04d', (int) $this->boilerYear) : '';
            $messung->boilerPower = $this->boilerPower !== '' ? sprintf('%04d', (int) $this->boilerPower) : '';
            $messung->cMIS_CONSUMO = '00000000';

            $messung->pruefeAnlageExistiert();

            // Debug-Log: Was wird tatsächlich gespeichert?
            Log::info('Messung vor save()', [
                'isNew' => $this->isNew,
                'id' => $messung->id ?? null,
                'dirty' => $messung->getDirty(),
            ]);

            $saved = $messung->save();

            if (!$saved) {
                Log::error('Messung::save() gab false zurück', [
                    'attributes' => $messung->getAttributes(),
                ]);
                session()->flash('error', 'Speichern fehlgeschlagen (save() returned false).');
                return;
            }

            Log::info('Messung erfolgreich gespeichert', [
                'id' => $messung->id,
                'cIM_CODICE' => $messung->cIM_CODICE,
            ]);

            // Nach erfolgreichem Save: frisch laden zur Bestätigung
            $check = Messung::find($messung->id);
            if (!$check) {
                Log::error('Messung nach save() nicht auffindbar', ['id' => $messung->id]);
                session()->flash('error', 'Messung wurde nicht persistiert.');
                return;
            }

            session()->flash('success', $this->isNew ? 'Messung wurde erstellt.' : 'Messung wurde aktualisiert.');
            return redirect()->route('messungen.index');

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('DB-Fehler beim Messung speichern', [
                'sql_state' => $e->getCode(),
                'message' => $e->getMessage(),
                'bindings' => $e->getBindings() ?? [],
            ]);
            session()->flash('error', 'Datenbankfehler: ' . $e->getMessage());
            return;

        } catch (\Exception $e) {
            Log::error('Unerwarteter Fehler beim Messung speichern', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Fehler: ' . $e->getMessage());
            return;
        }
    }

    /**
     * Helper: Integer-Feld formatieren, leer bleibt leer
     */
    private function formatIntField($value, int $width): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        return sprintf('%0' . $width . 'd', (int) $value);
    }

    /**
     * Helper: Float-Feld formatieren (NNNN.N), leer bleibt leer
     */
    private function formatFloatField($value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        return sprintf('%04.1f', (float) $value);
    }

    public function delete()
    {
        if ($this->messung) {
            $this->messung->delete();
            session()->flash('success', 'Messung wurde gelöscht.');
        }

        return redirect()->route('messungen.index');
    }

    public function getBrennstoffeProperty()
    {
        return Messung::BRENNSTOFFE;
    }

    public function getWirkungsgradProperty()
    {
        if (!$this->cMIS_PERD_FUMI) {
            return null;
        }
        return round(100 - (float) $this->cMIS_PERD_FUMI, 1);
    }

    public function render()
    {
        return view('livewire.messungen.messung-edit', [
            'brennstoffe' => $this->brennstoffe,
            'wirkungsgrad' => $this->wirkungsgrad,
        ]);
    }
}
