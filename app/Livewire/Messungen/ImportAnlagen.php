<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Impianto;
use Illuminate\Support\Facades\DB;

class ImportAnlagen extends Component
{
    use WithFileUploads;

    public $csvFile;
    public $importResult = null;
    public $isImporting = false;
    public $errors = [];

    protected $rules = [
        'csvFile' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
    ];

    protected $messages = [
        'csvFile.required' => 'Bitte eine CSV-Datei auswählen.',
        'csvFile.mimes' => 'Die Datei muss eine CSV oder TXT Datei sein.',
        'csvFile.max' => 'Die Datei darf maximal 10MB groß sein.',
    ];

    /**
     * CSV-Spalten-Mapping (Index => Feldname)
     * Basierend auf bestehendem Import-Code
     */
    private const CSV_MAPPING = [
        0  => 'Feld_a',   // Anlagen-Kodex
        1  => 'Feld_b',   // Gemeindecode
        2  => 'Feld_c',   // Nummer
        7  => 'Feld_h',   // Ort IT
        8  => 'Feld_i',   // Ort DE
        9  => 'Feld_j',   // Straße IT
        10 => 'Feld_k',   // Straße DE
        11 => 'Feld_l',   // Verwalter Name
        12 => 'Feld_m',   // Verwalter Ort
        13 => 'Feld_n',   // Hausnummer
        14 => 'Feld_o',   // Kontaktperson
        15 => 'Feld_p',   // Kontakt Ort IT
        16 => 'Feld_q',   // Kontakt Ort DE
        17 => 'Feld_r',   // Kontakt Straße IT
        18 => 'Feld_s',   // Kontakt Straße DE
        19 => 'Feld_t',   // Fraktion
        20 => 'Feld_u',   // Zusatz 1
        21 => 'Feld_v',   // Zusatz 2
        22 => 'Feld_w',   // Beschreibung
        23 => 'Feld_x',   // Status
        24 => 'Feld_y',   // Hersteller
        25 => 'Feld_z',   // Baujahr
        27 => 'Feld_ab',  // Leistung kW
    ];

    public function import()
    {
        $this->validate();
        
        $this->isImporting = true;
        $this->importResult = null;
        $this->errors = [];

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

                // Mindestens Feld_a muss vorhanden sein
                if (empty($values[0])) {
                    $result['errors']++;
                    $this->errors[] = "Zeile {$result['total']}: Feld_a (Kodex) ist leer.";
                    continue;
                }

                $feldA = trim($values[0]);

                // Prüfen ob bereits vorhanden
                if (Impianto::where('Feld_a', $feldA)->exists()) {
                    $result['skipped']++;
                    continue;
                }

                // Daten aus CSV extrahieren
                $data = [];
                foreach (self::CSV_MAPPING as $index => $field) {
                    $data[$field] = isset($values[$index]) ? trim($values[$index]) : null;
                }

                try {
                    Impianto::create($data);
                    $result['imported']++;
                } catch (\Exception $e) {
                    $result['errors']++;
                    $this->errors[] = "Zeile {$result['total']} (Kodex: {$feldA}): " . $e->getMessage();
                }
            }

            fclose($handle);
            DB::commit();

            $this->importResult = $result;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Import-Fehler: " . $e->getMessage();
        }

        $this->isImporting = false;
        $this->csvFile = null;
    }

    public function resetImport()
    {
        $this->csvFile = null;
        $this->importResult = null;
        $this->errors = [];
    }

    public function render()
    {
        return view('livewire.messungen.import-anlagen');
    }
}
