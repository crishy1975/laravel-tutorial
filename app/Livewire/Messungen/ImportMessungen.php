<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\Messung;
use App\Models\Impianto;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('layouts.app')]
class ImportMessungen extends Component
{
    use WithFileUploads;

    public $messFile;
    public $importResult = null;
    public $isImporting = false;
    public $importErrors = [];
    public $preview = [];
    public $showPreview = false;
    public $instrumentInfo = null;
    
    // Import-Optionen
    public $skipOhneAnlage = false; // ALLE Messungen importieren, auch ohne Anlage
    public $skipInvalidKodex = false; // ALLE Kodexe akzeptieren

    protected $rules = [
        'messFile' => 'required|file|mimes:xml,txt|max:20480',
    ];

    protected $messages = [
        'messFile.required' => 'Bitte eine Messdaten-Datei auswählen.',
        'messFile.mimes' => 'Die Datei muss eine XML Datei sein.',
        'messFile.max' => 'Die Datei darf maximal 20MB groß sein.',
    ];

    /**
     * Brennstoff-Mapping: FuelId -> DB-Werte
     * Quelle: Feldzuordnung_Komplett.xlsx - Sheet "Brennstoff_Mapping"
     */
    private const FUEL_MAP = [
        'FUEL_LIGHT_OIL' => ['nr' => 1, 'text' => 'Heizöl/gasolio'],
        'FUEL_HEAVY_OIL' => ['nr' => 1, 'text' => 'Heizöl/gasolio'],
        'FUEL_NAT_GAS'   => ['nr' => 3, 'text' => 'Erdgas/metano'],
        'FUEL_PROPANE'   => ['nr' => 6, 'text' => 'Flüssiggas/gas liquido'],
        'FUEL_BUTANE'    => ['nr' => 6, 'text' => 'Flüssiggas/gas liquido'],
        'FUEL_PELLETS'   => ['nr' => 7, 'text' => 'Pellet'],
        'FUEL_WOOD'      => ['nr' => 7, 'text' => 'Holz/legna'],
    ];

    /**
     * Messtyp-Mapping: XML-Element -> DB-Wert
     */
    private const MEASUREMENT_TYPE_MAP = [
        'CombustionMeasurement'   => '001',
        'Uni10389_1Measurement'   => '002',
        'Uni10389_2Measurement'   => '003',
    ];

    /**
     * Extrahiert einen Wert mit bestimmter Unit aus XML-Element
     * Sucht nach dem Element mit der passenden Unit-Attribut
     */
    private function getValueWithUnit(\SimpleXMLElement $parent, string $element, string $unit): ?string
    {
        foreach ($parent->children() as $child) {
            if ($child->getName() === $element && (string)$child['Unit'] === $unit) {
                return (string)$child;
            }
        }
        return null;
    }

    /**
     * Extrahiert den ersten Wert eines Elements (ohne Unit-Filter)
     */
    private function getFirstValue(\SimpleXMLElement $parent, string $element): ?string
    {
        foreach ($parent->children() as $child) {
            if ($child->getName() === $element) {
                return (string)$child;
            }
        }
        return null;
    }

    /**
     * Parst eine einzelne Messung aus dem XML
     * Mapping gemäß Feldzuordnung_Komplett.xlsx
     */
    private function parseMeasurement(
        \SimpleXMLElement $customer, 
        \SimpleXMLElement $fireplace, 
        \SimpleXMLElement $measurement, 
        string $measurementType
    ): array {
        // === Anlagendaten ===
        $customerId = (string)$customer['CustomerId'];
        $customerName = (string)($customer->Address->n ?? '');
        $fireplaceNumber = (string)$fireplace['FireplaceNumber'];
        
        // === Brennstoff ===
        $fuelId = (string)($fireplace->Fuel['FuelId'] ?? 'FUEL_NAT_GAS');
        $fuelInfo = self::FUEL_MAP[$fuelId] ?? self::FUEL_MAP['FUEL_NAT_GAS'];
        
        // === Datum und Uhrzeit ===
        $dateStr = $this->getFirstValue($measurement, 'Date') ?? '';
        $timeStr = $this->getFirstValue($measurement, 'Time') ?? '';
        
        // Datum: 2024-03-01 -> cMIS_DATA: 01032024, cMIS_DATA2: 01.03.2024
        $dateDMY = '';
        $dateFormatted = '';
        if ($dateStr) {
            try {
                $dateObj = Carbon::createFromFormat('Y-m-d', $dateStr);
                $dateDMY = $dateObj->format('dmY');
                $dateFormatted = $dateObj->format('d.m.Y');
            } catch (\Exception $e) {
                // Fallback
                $dateDMY = str_replace('-', '', $dateStr);
            }
        }
        
        // === Messwerte ===
        // O2 und CO2: UNIT_VOL_PERCENT
        $o2 = $this->getValueWithUnit($measurement, 'O2', 'UNIT_VOL_PERCENT');
        $co2 = $this->getValueWithUnit($measurement, 'CO2', 'UNIT_VOL_PERCENT');
        
        // CO: CO_Norm in UNIT_MG_M3 (gemäß Mapping)
        $co = $this->getValueWithUnit($measurement, 'CO_Norm', 'UNIT_MG_M3');
        
        // NOx: NOx_Norm in UNIT_MG_M3 (gemäß Mapping)
        $nox = $this->getValueWithUnit($measurement, 'NOx_Norm', 'UNIT_MG_M3');
        
        // Temperaturen: UNIT_DEG_C
        $tFlue = $this->getValueWithUnit($measurement, 'T_Flue', 'UNIT_DEG_C');
        $tAir = $this->getValueWithUnit($measurement, 'T_Air', 'UNIT_DEG_C');
        $tBoiler = $this->getValueWithUnit($measurement, 'T_Boiler', 'UNIT_DEG_C');
        
        // Abgasverlust: Q_Flue in UNIT_PERCENT
        $qFlue = $this->getValueWithUnit($measurement, 'Q_Flue', 'UNIT_PERCENT');
        
        // Rußzahl: SootAvg oder SootNumber
        $soot = $this->getFirstValue($measurement, 'SootAvg') 
             ?? $this->getFirstValue($measurement, 'SootNumber') 
             ?? '0';
        
        // Ölspuren: OilDeriv (INVERTIERT! XML false = DB 1 = keine Ölspuren)
        $oilDerivXml = $this->getFirstValue($measurement, 'OilDeriv') ?? 'false';
        $oilDerivDb = ($oilDerivXml === 'true') ? '0' : '1';
        
        // === Anlagendaten aus impianti ===
        $impianto = Impianto::where('Feld_a', $customerId)->first();
        $codeInImpianti = $impianto ? 1 : 0;
        $anlageName = $impianto->Feld_w ?? $customerName;
        
        // Baujahr und Leistung: Zuerst aus XML (Boiler), dann aus impianti
        $boilerYear = '';
        $boilerPower = '';
        
        if (isset($fireplace->Boiler)) {
            $xmlYear = (string)($fireplace->Boiler->BoilerYear ?? '');
            $xmlPower = (string)($fireplace->Boiler->BoilerPower ?? '');
            if ($xmlYear && $xmlYear !== '0') $boilerYear = $xmlYear;
            if ($xmlPower && $xmlPower !== '0') $boilerPower = $xmlPower;
        }
        
        // Fallback auf impianti
        if (!$boilerYear && $impianto) {
            $boilerYear = $impianto->Feld_z ?? '';
        }
        if (!$boilerPower && $impianto) {
            $boilerPower = $impianto->Feld_ab ?? '';
        }
        
        // Messtyp
        $tipo = self::MEASUREMENT_TYPE_MAP[$measurementType] ?? '001';
        
        // === Rückgabe mit korrekter Formatierung ===
        return [
            // Pflichtfelder
            'cIM_CODICE' => sprintf('%06d', (int)$customerId),
            'cIM_NAME' => $anlageName,
            'cMIS_TIPO' => $tipo,
            'cMIS_STADIO' => sprintf('%01d', (int)$fireplaceNumber),
            'cMIS_DATA' => $dateDMY,
            'cMIS_DATA2' => $dateFormatted,
            'cMIS_ORA' => $timeStr,
            
            // Brennstoff
            'cMIS_COMBUSTIBILE' => $fuelId,
            'cMIS_COMBUSTIBILE_N' => $fuelInfo['nr'],
            'cMIS_COMBUSTIBILE_P' => $fuelInfo['text'],
            
            // Temperaturen
            'cMIS_T_GAS_COMB' => $tFlue ? sprintf('%03d', (int)round((float)$tFlue)) : '',
            'cMIS_T_ARIA_COMB' => $tAir ? sprintf('%02d', (int)round((float)$tAir)) : '',
            'cMIS_T_LIQ_CONV' => $tBoiler ? sprintf('%03d', (int)round((float)$tBoiler)) : '060',
            
            // Messwerte
            'cMIS_OSSIGENO' => $o2 ? sprintf('%04.1f', (float)$o2) : '',
            'cMIS_ANIDRIDE_CARBONICA' => $co2 ? sprintf('%04.1f', (float)$co2) : '',
            'cMIS_MONOSSSIDO' => $co ? sprintf('%04d', (int)round((float)$co)) : '',
            'cMIS_BIOSSIDO_AZOTO' => $nox ? sprintf('%04d', (int)round((float)$nox)) : '',
            'cMIS_PERD_FUMI' => $qFlue ? sprintf('%04.1f', (float)$qFlue) : '',
            
            // Sonstiges
            'cMIS_IND_OPACITA' => sprintf('%01d', (int)$soot),
            'cMIS_TRACCE_OLEO' => $oilDerivDb,
            'strEsito' => '', // Wird via setzeErgebnisAutomatisch() berechnet
            'boilerYear' => $boilerYear ? sprintf('%04d', (int)$boilerYear) : '',
            'boilerPower' => $boilerPower ? sprintf('%04d', (int)$boilerPower) : '',
            'cMIS_CONSUMO' => '00000000',
            'codeInImpianti' => $codeInImpianti,
            
            // Zusätzliche Felder für Vorschau (werden vor Insert entfernt)
            '_customerName' => $customerName,
            '_measurementType' => $measurementType,
            '_rawDate' => $dateStr,
            '_rawTime' => $timeStr,
        ];
    }

    /**
     * Parst die XML-Datei und gibt alle Messungen zurück
     */
    private function parseXmlFile(string $path): array
    {
        $measurements = [];
        
        $xmlContent = file_get_contents($path);
        if ($xmlContent === false) {
            throw new \Exception('Datei konnte nicht gelesen werden.');
        }
        
        // Namespace entfernen für einfacheres Parsing
        $xmlContent = preg_replace('/xmlns="[^"]+"/', '', $xmlContent);
        
        $xml = simplexml_load_string($xmlContent);
        if ($xml === false) {
            $errors = libxml_get_errors();
            $errorMsg = !empty($errors) ? $errors[0]->message : 'Unbekannter XML-Fehler';
            throw new \Exception('XML-Parsing-Fehler: ' . $errorMsg);
        }
        
        // Instrument-Info speichern
        if (isset($xml->Instrument)) {
            $this->instrumentInfo = [
                'manufacturer' => (string)($xml->Instrument->Manufacturer ?? ''),
                'type' => (string)($xml->Instrument->Type ?? ''),
                'serialNumber' => (string)($xml->Instrument->SerialNumber ?? ''),
                'numCustomers' => (string)($xml->Instrument->NumCustomers ?? ''),
                'numMeasurements' => (string)($xml->Instrument->NumMeasurements ?? ''),
                'lastCheckDate' => (string)($xml->Instrument->LastCheckDate ?? ''),
            ];
        }
        
        // Alle Kunden durchgehen
        foreach ($xml->Customer as $customer) {
            foreach ($customer->Fireplace as $fireplace) {
                // Alle Messungstypen prüfen
                foreach (['CombustionMeasurement', 'Uni10389_1Measurement', 'Uni10389_2Measurement'] as $measurementType) {
                    if (isset($fireplace->$measurementType)) {
                        $measurements[] = $this->parseMeasurement(
                            $customer, 
                            $fireplace, 
                            $fireplace->$measurementType,
                            $measurementType
                        );
                    }
                }
            }
        }
        
        return $measurements;
    }

    /**
     * Vorschau der ersten Messungen generieren
     */
    public function generatePreview()
    {
        $this->validate();
        
        $this->preview = [];
        $this->showPreview = true;
        $this->instrumentInfo = null;
        $this->importErrors = [];

        try {
            $path = $this->messFile->getRealPath();
            $measurements = $this->parseXmlFile($path);
            
            // Erste 10 Messungen für Vorschau
            $this->preview = array_slice($measurements, 0, 10);
            
        } catch (\Exception $e) {
            $this->importErrors[] = 'Fehler beim Lesen der Datei: ' . $e->getMessage();
        }
    }

    /**
     * Import durchführen
     */
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
            'skippedDuplicate' => 0,
            'skippedOhneAnlage' => 0,
            'skippedInvalidKodex' => 0,
            'errors' => 0,
        ];

        try {
            $path = $this->messFile->getRealPath();
            $measurements = $this->parseXmlFile($path);
            
            DB::beginTransaction();

            foreach ($measurements as $data) {
                $result['total']++;
                
                // Temporäre Preview-Felder entfernen
                unset(
                    $data['_customerName'], 
                    $data['_measurementType'], 
                    $data['_rawDate'], 
                    $data['_rawTime']
                );

                // Validierung: Kodex-Länge prüfen (ursprünglich max 6 Zeichen, jetzt 16)
                if (strlen($data['cIM_CODICE']) > 16) {
                    if ($this->skipInvalidKodex) {
                        $result['skippedInvalidKodex']++;
                        continue;
                    } else {
                        // Kodex auf 16 Zeichen kürzen
                        $data['cIM_CODICE'] = substr($data['cIM_CODICE'], -16);
                    }
                }

                // Anlage nicht gefunden - zählen für Statistik
                if ($data['codeInImpianti'] === 0) {
                    $result['skippedOhneAnlage']++; // Nur zählen, nicht überspringen
                    if ($this->skipOhneAnlage) {
                        continue;
                    }
                    // Import erfolgt trotzdem (Foreign Key wurde entfernt)
                }

                // Duplikat-Check: Kodex + Datum + Uhrzeit + Stadio
                $exists = Messung::where('cIM_CODICE', $data['cIM_CODICE'])
                    ->where('cMIS_DATA', $data['cMIS_DATA'])
                    ->where('cMIS_ORA', $data['cMIS_ORA'])
                    ->where('cMIS_STADIO', $data['cMIS_STADIO'])
                    ->exists();

                if ($exists) {
                    $result['skippedDuplicate']++;
                    continue;
                }

                try {
                    $messung = new Messung($data);
                    
                    // Ergebnis automatisch berechnen (GrenzwertService)
                    $messung->setzeErgebnisAutomatisch();
                    
                    $messung->save();
                    $result['imported']++;
                    
                } catch (\Exception $e) {
                    $result['errors']++;
                    $this->importErrors[] = "Kodex {$data['cIM_CODICE']}, Datum {$data['cMIS_DATA2']}: " . $e->getMessage();
                }
            }

            DB::commit();
            $this->importResult = $result;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->importErrors[] = "Import-Fehler: " . $e->getMessage();
        }

        $this->isImporting = false;
        $this->messFile = null;
        $this->showPreview = false;
    }

    public function resetImport()
    {
        $this->messFile = null;
        $this->importResult = null;
        $this->importErrors = [];
        $this->preview = [];
        $this->showPreview = false;
        $this->instrumentInfo = null;
    }

    public function render()
    {
        return view('livewire.messungen.import-messungen');
    }
}
