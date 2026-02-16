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
    public $importErrors = [];  // NICHT $errors – das ist reserviert von Laravel!

    protected $rules = [
        'csvFile' => 'required|file|mimes:csv,txt|max:10240',
    ];

    protected $messages = [
        'csvFile.required' => 'Bitte eine CSV-Datei auswählen.',
        'csvFile.mimes' => 'Die Datei muss eine CSV oder TXT Datei sein.',
        'csvFile.max' => 'Die Datei darf maximal 10MB groß sein.',
    ];

    private const CSV_MAPPING = [
        0  => 'Feld_a',
        1  => 'Feld_b',
        2  => 'Feld_c',
        7  => 'Feld_h',
        8  => 'Feld_i',
        9  => 'Feld_j',
        10 => 'Feld_k',
        11 => 'Feld_l',
        12 => 'Feld_m',
        13 => 'Feld_n',
        14 => 'Feld_o',
        15 => 'Feld_p',
        16 => 'Feld_q',
        17 => 'Feld_r',
        18 => 'Feld_s',
        19 => 'Feld_t',
        20 => 'Feld_u',
        21 => 'Feld_v',
        22 => 'Feld_w',
        23 => 'Feld_x',
        24 => 'Feld_y',
        25 => 'Feld_z',
        27 => 'Feld_ab',
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
