<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\Impianto;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
class ImportAnlagen extends Component
{
    use WithFileUploads;

    public $csvFile;
    public $importResult = null;
    public $isImporting = false;
    public $importErrors = [];  // NICHT $errors – reserviert von Laravel!

    protected $rules = [
        'csvFile' => 'required|file|mimes:csv,txt|max:10240',
    ];

    protected $messages = [
        'csvFile.required' => 'Bitte eine CSV-Datei auswählen.',
        'csvFile.mimes' => 'Die Datei muss eine CSV oder TXT Datei sein.',
        'csvFile.max' => 'Die Datei darf maximal 10MB groß sein.',
    ];

    /*
    |--------------------------------------------------------------------------
    | CSV-Spalten-Mapping: CSV-Index => Datenbank-Feld
    |--------------------------------------------------------------------------
    */
    private const CSV_MAPPING = [
        0  => 'Feld_a',   // Anlagen-Kodex (PK)
        1  => 'Feld_b',   // Kaminkehrer-Kodex1
        2  => 'Feld_c',   // Kaminkehrer-Kodex2
        7  => 'Feld_h',   // Gemeinde Aufstellungsort (IT)
        8  => 'Feld_i',   // Gemeinde Aufstellungsort (DE)
        9  => 'Feld_J',   // Fraktion Aufstellungsort (IT)
        10 => 'Feld_K',   // Fraktion Aufstellungsort (DE)
        11 => 'Feld_l',   // Straße Aufstellungsort (IT)
        12 => 'Feld_m',   // Straße Aufstellungsort (DE)
        13 => 'Feld_n',   // Hausnummer Aufstellungsort
        14 => 'Feld_o',   // Name Betreiber
        15 => 'Feld_p',   // Gemeinde Betreiber (IT)
        16 => 'Feld_q',   // Gemeinde Betreiber (DE)
        17 => 'Feld_r',   // Fraktion Betreiber (IT)
        18 => 'Feld_s',   // Fraktion Betreiber (DE)
        19 => 'Feld_t',   // Straße Betreiber (IT)
        20 => 'Feld_u',   // Straße Betreiber (DE)
        21 => 'Feld_v',   // Hausnummer Betreiber
        22 => 'Feld_w',   // Name Aufstellungsort
        23 => 'Feld_x',   // Status
        24 => 'Feld_y',   // Hersteller Kessel
        25 => 'Feld_z',   // Baujahr Kessel
        27 => 'Feld_ab',  // Leistung kW
    ];

    public function import()
    {
        $this->validate();

        $this->isImporting = true;
        $this->importResult = null;
        $this->importErrors = [];

        $result = [
            'total' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        try {
            $path = $this->csvFile->getRealPath();
            $handle = fopen($path, 'r');

            if ($handle === false) {
                throw new \Exception('Datei konnte nicht geöffnet werden.');
            }

            DB::beginTransaction();

            while (($values = fgetcsv($handle, 0, ";")) !== false) {
                $result['total']++;

                if (empty($values[0])) {
                    $result['errors']++;
                    $this->importErrors[] = "Zeile {$result['total']}: Feld_a (Kodex) ist leer.";
                    continue;
                }

                $feldA = trim($values[0]);

                if (Impianto::where('Feld_a', $feldA)->exists()) {
                    $result['skipped']++;
                    continue;
                }

                $data = [];
                foreach (self::CSV_MAPPING as $index => $field) {
                    $data[$field] = isset($values[$index]) ? trim($values[$index]) : null;
                }

                try {
                    Impianto::create($data);
                    $result['imported']++;
                } catch (\Exception $e) {
                    $result['errors']++;
                    $this->importErrors[] = "Zeile {$result['total']} (Kodex: {$feldA}): " . $e->getMessage();
                }
            }

            fclose($handle);
            DB::commit();

            $this->importResult = $result;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->importErrors[] = "Import-Fehler: " . $e->getMessage();
        }

        $this->isImporting = false;
        $this->csvFile = null;
    }

    public function resetImport()
    {
        $this->csvFile = null;
        $this->importResult = null;
        $this->importErrors = [];
    }

    public function render()
    {
        return view('livewire.messungen.import-anlagen');
    }
}
