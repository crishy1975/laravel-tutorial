<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Impianto;
use App\Models\Messung;
use App\Services\GrenzwertService;
use Carbon\Carbon;

#[Layout('layouts.app')]
class AnlagenListe extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filter
    public $filterKodex = '';
    public $filterBeschreibung = '';   // sucht in Feld_w (Name Aufstellungsort)
    public $filterStrasse = '';        // sucht in Feld_m/Feld_l (Straße Aufstellungsort DE/IT)
    public $filterOrt = '';            // sucht in Feld_i/Feld_h (Gemeinde Aufstellungsort DE/IT)
    public $filterHersteller = '';     // sucht in Feld_y (Hersteller Kessel)
    public $filterGemessen = '';
    public $filterExportStatus = '';   // '' | '1' (exportiert) | '0' (nicht exportiert)
    public $filterJahr;

    // Event-Listener (Modal-Refresh nach Export)
    protected $listeners = ['messungen-exported' => 'refreshAfterExport'];

    // Sortierung
    public $sortField = 'Feld_i';
    public $sortDirection = 'asc';

    // Modal: Neue Messung
    public $showMessungModal = false;
    public $selectedAnlage = null;
    public $letzteMessung = null;
    public $modalError = null;
    public $messung = [
        'cMIS_STADIO' => '1',
        'cMIS_DATA2' => '',
        'cMIS_ORA' => '',
        'cMIS_COMBUSTIBILE' => 'FUEL_NAT_GAS',
        'cMIS_OSSIGENO' => '',
        'cMIS_ANIDRIDE_CARBONICA' => '',
        'cMIS_MONOSSSIDO' => '',
        'cMIS_BIOSSIDO_AZOTO' => '',
        'cMIS_T_GAS_COMB' => '',
        'cMIS_T_ARIA_COMB' => '',
        'cMIS_T_LIQ_CONV' => '',
        'cMIS_PERD_FUMI' => '',
        'cMIS_IND_OPACITA' => '0',
        'cMIS_TRACCE_OLEO' => '1',
    ];
    public $grenzwerte = null;

    protected $queryString = [
        'filterKodex' => ['except' => ''],
        'filterBeschreibung' => ['except' => ''],
        'filterStrasse' => ['except' => ''],
        'filterOrt' => ['except' => ''],
        'filterHersteller' => ['except' => ''],
        'filterGemessen' => ['except' => ''],
        'filterExportStatus' => ['except' => ''],
        'filterJahr' => ['except' => ''],
    ];

    // Brennstoff-Mapping
    public const BRENNSTOFFE = [
        'FUEL_LIGHT_OIL' => ['nr' => 1, 'text' => 'Heizöl/gasolio'],
        'FUEL_HEAVY_OIL' => ['nr' => 1, 'text' => 'Heizöl/gasolio'],
        'FUEL_NAT_GAS'   => ['nr' => 3, 'text' => 'Erdgas/metano'],
        'FUEL_PROPANE'   => ['nr' => 6, 'text' => 'Flüssiggas/gas liquido'],
        'FUEL_BUTANE'    => ['nr' => 6, 'text' => 'Flüssiggas/gas liquido'],
        'FUEL_PELLETS'   => ['nr' => 4, 'text' => 'Pellets'],
        'FUEL_WOOD'      => ['nr' => 5, 'text' => 'Holz/legna'],
    ];

    public function mount()
    {
        if (!request()->hasAny(['filterKodex', 'filterBeschreibung', 'filterStrasse', 'filterOrt', 'filterHersteller', 'filterGemessen', 'filterExportStatus', 'filterJahr'])) {
            $saved = session('anlagen_filter', []);
            if (!empty($saved)) {
                $this->filterKodex = $saved['filterKodex'] ?? '';
                $this->filterBeschreibung = $saved['filterBeschreibung'] ?? '';
                $this->filterStrasse = $saved['filterStrasse'] ?? '';
                $this->filterOrt = $saved['filterOrt'] ?? '';
                $this->filterHersteller = $saved['filterHersteller'] ?? '';
                $this->filterGemessen = $saved['filterGemessen'] ?? '';
                $this->filterExportStatus = $saved['filterExportStatus'] ?? '';
                $this->filterJahr = $saved['filterJahr'] ?? date('Y');
                $this->sortField = $saved['sortField'] ?? 'Feld_i';
                $this->sortDirection = $saved['sortDirection'] ?? 'asc';
            } else {
                $this->filterJahr = date('Y');
            }
        } else {
            $this->filterJahr = $this->filterJahr ?: date('Y');
        }
    }

    private function saveToSession(): void
    {
        session(['anlagen_filter' => [
            'filterKodex' => $this->filterKodex,
            'filterBeschreibung' => $this->filterBeschreibung,
            'filterStrasse' => $this->filterStrasse,
            'filterOrt' => $this->filterOrt,
            'filterHersteller' => $this->filterHersteller,
            'filterGemessen' => $this->filterGemessen,
            'filterExportStatus' => $this->filterExportStatus,
            'filterJahr' => $this->filterJahr,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]]);
    }

    public function applyFilters()
    {
        $this->resetPage();
        $this->saveToSession();
    }

    public function updatingFilterKodex()       { }
    public function updatingFilterBeschreibung() { }
    public function updatingFilterStrasse()      { }
    public function updatingFilterOrt()          { }
    public function updatingFilterHersteller()   { }
    public function updatingFilterGemessen()     { }
    public function updatingFilterExportStatus() { }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->saveToSession();
    }

    public function resetFilters()
    {
        $this->filterKodex = '';
        $this->filterBeschreibung = '';
        $this->filterStrasse = '';
        $this->filterOrt = '';
        $this->filterHersteller = '';
        $this->filterGemessen = '';
        $this->filterExportStatus = '';
        $this->filterJahr = date('Y');
        $this->resetPage();
        $this->saveToSession();
    }

    // ========== Amt-Export ==========

    /**
     * Öffnet das Amt-Export-Modal (via Event).
     */
    public function openAmtExport()
    {
        $this->dispatch('open-amt-export-modal', jahr: (int) $this->filterJahr);
    }

    /**
     * Wird nach erfolgreichem Export aufgerufen (vom AmtExport-Component).
     */
    public function refreshAfterExport()
    {
        $this->resetPage();
    }

    // ========== Modal: Neue Messung ==========

    public function openMessungModal($anlageKodex)
    {
        $this->selectedAnlage = Impianto::where('Feld_a', $anlageKodex)->first();
        
        if (!$this->selectedAnlage) {
            return;
        }

        $this->letzteMessung = null;

        // Messung-Daten zurücksetzen
        $this->messung = [
            'cMIS_STADIO' => '1',
            'cMIS_DATA2' => date('d.m.Y'),
            'cMIS_ORA' => date('H:i'),
            'cMIS_COMBUSTIBILE' => 'FUEL_NAT_GAS',
            'cMIS_OSSIGENO' => '',
            'cMIS_ANIDRIDE_CARBONICA' => '',
            'cMIS_MONOSSSIDO' => '',
            'cMIS_BIOSSIDO_AZOTO' => '',
            'cMIS_T_GAS_COMB' => '',
            'cMIS_T_ARIA_COMB' => '',
            'cMIS_T_LIQ_CONV' => '',
            'cMIS_PERD_FUMI' => '',
            'cMIS_IND_OPACITA' => '0',
            'cMIS_TRACCE_OLEO' => '1',
        ];
        
        $this->grenzwerte = null;
        $this->showMessungModal = true;
    }

    public function openMessungModalMitLetzer($anlageKodex)
    {
        $this->selectedAnlage = Impianto::where('Feld_a', $anlageKodex)->first();
        
        if (!$this->selectedAnlage) {
            return;
        }

        // Letzte Messung dieser Anlage im aktuellen Jahr laden
        $this->letzteMessung = Messung::where('cIM_CODICE', $anlageKodex)
            ->whereRaw("RIGHT(cMIS_DATA, 4) = ?", [$this->filterJahr])
            ->orderBy('cMIS_DATA', 'desc')
            ->orderBy('cMIS_ORA', 'desc')
            ->first();

        if (!$this->letzteMessung) {
            return;
        }

        // Werte aus letzter Messung übernehmen
        $this->messung = [
            'cMIS_STADIO' => $this->letzteMessung->cMIS_STADIO ?? '1',
            'cMIS_DATA2' => $this->letzteMessung->cMIS_DATA2 ?? date('d.m.Y'),
            'cMIS_ORA' => $this->letzteMessung->cMIS_ORA ?? date('H:i'),
            'cMIS_COMBUSTIBILE' => $this->letzteMessung->cMIS_COMBUSTIBILE ?? 'FUEL_NAT_GAS',
            'cMIS_OSSIGENO' => $this->letzteMessung->cMIS_OSSIGENO ?? '',
            'cMIS_ANIDRIDE_CARBONICA' => $this->letzteMessung->cMIS_ANIDRIDE_CARBONICA ?? '',
            'cMIS_MONOSSSIDO' => $this->letzteMessung->cMIS_MONOSSSIDO ?? '',
            'cMIS_BIOSSIDO_AZOTO' => $this->letzteMessung->cMIS_BIOSSIDO_AZOTO ?? '',
            'cMIS_T_GAS_COMB' => $this->letzteMessung->cMIS_T_GAS_COMB ?? '',
            'cMIS_T_ARIA_COMB' => $this->letzteMessung->cMIS_T_ARIA_COMB ?? '',
            'cMIS_T_LIQ_CONV' => $this->letzteMessung->cMIS_T_LIQ_CONV ?? '',
            'cMIS_PERD_FUMI' => $this->letzteMessung->cMIS_PERD_FUMI ?? '',
            'cMIS_IND_OPACITA' => $this->letzteMessung->cMIS_IND_OPACITA ?? '0',
            'cMIS_TRACCE_OLEO' => $this->letzteMessung->cMIS_TRACCE_OLEO ?? '1',
        ];
        
        $this->grenzwerte = null;
        $this->berechneGrenzwerte();
        $this->showMessungModal = true;
    }

    public function closeMessungModal()
    {
        $this->showMessungModal = false;
        $this->selectedAnlage = null;
        $this->letzteMessung = null;
        $this->messung = [];
        $this->grenzwerte = null;
        $this->modalError = null;
    }

    public function updatedMessung($value, $key)
    {
        // Grenzwerte berechnen wenn relevante Werte geändert werden
        if (in_array($key, ['cMIS_MONOSSSIDO', 'cMIS_BIOSSIDO_AZOTO', 'cMIS_IND_OPACITA', 'cMIS_TRACCE_OLEO', 'cMIS_COMBUSTIBILE'])) {
            $this->berechneGrenzwerte();
        }
    }

    protected function berechneGrenzwerte()
    {
        if (!$this->selectedAnlage) {
            return;
        }

        $brennstoff = $this->messung['cMIS_COMBUSTIBILE'] ?? 'FUEL_NAT_GAS';
        $leistung = (int) ($this->selectedAnlage->Feld_ab ?? 0);
        $baujahr = (int) ($this->selectedAnlage->Feld_z ?? 2000);
        $co = (int) ($this->messung['cMIS_MONOSSSIDO'] ?? 0);
        $nox = (int) ($this->messung['cMIS_BIOSSIDO_AZOTO'] ?? 0);
        $russ = (int) ($this->messung['cMIS_IND_OPACITA'] ?? 0);
        $oelspuren = ($this->messung['cMIS_TRACCE_OLEO'] ?? '1') === '0'; // '0' = Ja = true

        // GrenzwertService nutzen
        $service = new GrenzwertService();
        $service->pruefeGrenzwerte($brennstoff, $baujahr, $leistung, $co, $nox, $russ, $oelspuren);
        $result = $service->getResult();

        // Grenzwerte-Info holen
        $grenzwertInfo = GrenzwertService::getGrenzwerteInfo($brennstoff, $baujahr, $leistung);

        // Für die Anzeige im Modal aufbereiten
        $this->grenzwerte = [
            'co' => [
                'grenzwert' => $result['maxCo'],
                'status' => $result['coUeberschritten'] ? 'rot' : ($co > $result['maxCo'] * 0.8 ? 'gelb' : 'gruen'),
            ],
            'nox' => [
                'grenzwert' => $result['maxNoX'],
                'status' => $result['noxUeberschritten'] ? 'rot' : ($nox > $result['maxNoX'] * 0.8 ? 'gelb' : 'gruen'),
            ],
            'russ' => [
                'grenzwert' => $result['maxSoot'],
                'status' => $result['russUeberschritten'] ? 'rot' : 'gruen',
            ],
            'oel' => [
                'grenzwert' => 0,
                'status' => $result['oelspurenUeberschritten'] ? 'rot' : 'gruen',
            ],
        ];
    }

    public function saveMessung()
    {
        $this->modalError = null;
        
        // Validierung
        $this->validate([
            'messung.cMIS_STADIO' => 'required',
            'messung.cMIS_DATA2' => 'required',
            'messung.cMIS_COMBUSTIBILE' => 'required',
        ], [
            'messung.cMIS_STADIO.required' => 'Stadio ist erforderlich.',
            'messung.cMIS_DATA2.required' => 'Datum ist erforderlich.',
            'messung.cMIS_COMBUSTIBILE.required' => 'Brennstoff ist erforderlich.',
        ]);

        if (!$this->selectedAnlage) {
            $this->modalError = 'Keine Anlage ausgewählt.';
            return;
        }

        try {
            // Datum konvertieren
            $datumParts = explode('.', $this->messung['cMIS_DATA2'] ?? '');
            $dateDMY = '';
            if (count($datumParts) === 3) {
                $dateDMY = $datumParts[0] . $datumParts[1] . $datumParts[2];
            }

            // Brennstoff-Info
            $fuelInfo = self::BRENNSTOFFE[$this->messung['cMIS_COMBUSTIBILE'] ?? 'FUEL_NAT_GAS'] ?? self::BRENNSTOFFE['FUEL_NAT_GAS'];

            // Komma zu Punkt konvertieren für Dezimalwerte
            $dezimalFelder = ['cMIS_OSSIGENO', 'cMIS_ANIDRIDE_CARBONICA', 'cMIS_PERD_FUMI', 
                              'cMIS_T_GAS_COMB', 'cMIS_T_ARIA_COMB', 'cMIS_T_LIQ_CONV'];
            foreach ($dezimalFelder as $feld) {
                if (isset($this->messung[$feld])) {
                    $this->messung[$feld] = str_replace(',', '.', $this->messung[$feld]);
                }
            }

            // Ganzzahl-Felder runden (max 3 Stellen)
            $intFelder = ['cMIS_MONOSSSIDO', 'cMIS_BIOSSIDO_AZOTO', 'cMIS_T_GAS_COMB', 'cMIS_T_ARIA_COMB', 'cMIS_T_LIQ_CONV'];
            foreach ($intFelder as $feld) {
                if (isset($this->messung[$feld]) && $this->messung[$feld] !== '') {
                    $this->messung[$feld] = (string) round((float) $this->messung[$feld]);
                }
            }

            // Grenzwerte berechnen für Ergebnis
            $this->berechneGrenzwerte();
            $esito = '1'; // Standard: positiv
            if ($this->grenzwerte) {
                if (($this->grenzwerte['co']['status'] ?? '') === 'rot' ||
                    ($this->grenzwerte['nox']['status'] ?? '') === 'rot' ||
                    ($this->grenzwerte['russ']['status'] ?? '') === 'rot' ||
                    ($this->grenzwerte['oel']['status'] ?? '') === 'rot') {
                    $esito = '0'; // negativ
                }
            }

            // Daten für Speicherung
            $data = [
                'cIM_CODICE' => $this->selectedAnlage->Feld_a,
                'cIM_NAME' => $this->selectedAnlage->Feld_w ?? '',
                'cMIS_TIPO' => '001',
                'cMIS_STADIO' => $this->messung['cMIS_STADIO'] ?? '1',
                'cMIS_DATA' => $dateDMY,
                'cMIS_DATA2' => $this->messung['cMIS_DATA2'] ?? '',
                'cMIS_ORA' => $this->messung['cMIS_ORA'] ?? '',
                'strEsito' => $esito,
                'cMIS_COMBUSTIBILE' => $this->messung['cMIS_COMBUSTIBILE'] ?? 'FUEL_NAT_GAS',
                'cMIS_COMBUSTIBILE_N' => $fuelInfo['nr'],
                'cMIS_COMBUSTIBILE_P' => $fuelInfo['text'],
                'cMIS_OSSIGENO' => $this->messung['cMIS_OSSIGENO'] ?? '',
                'cMIS_ANIDRIDE_CARBONICA' => $this->messung['cMIS_ANIDRIDE_CARBONICA'] ?? '',
                'cMIS_MONOSSSIDO' => $this->messung['cMIS_MONOSSSIDO'] ?? '',
                'cMIS_BIOSSIDO_AZOTO' => $this->messung['cMIS_BIOSSIDO_AZOTO'] ?? '',
                'cMIS_T_GAS_COMB' => $this->messung['cMIS_T_GAS_COMB'] ?? '',
                'cMIS_T_ARIA_COMB' => $this->messung['cMIS_T_ARIA_COMB'] ?? '',
                'cMIS_T_LIQ_CONV' => $this->messung['cMIS_T_LIQ_CONV'] ?? '',
                'cMIS_PERD_FUMI' => $this->messung['cMIS_PERD_FUMI'] ?? '',
                'cMIS_IND_OPACITA' => $this->messung['cMIS_IND_OPACITA'] ?? '0',
                'cMIS_TRACCE_OLEO' => $this->messung['cMIS_TRACCE_OLEO'] ?? '1',
                'boilerYear' => $this->selectedAnlage->Feld_z ?? '',
                'boilerPower' => $this->selectedAnlage->Feld_ab ?? '',
                'codeInImpianti' => 1,
            ];

            // Update wenn bestehende Messung, sonst Create
            if ($this->letzteMessung) {
                $this->letzteMessung->update($data);
                $message = 'Messung wurde aktualisiert.';
            } else {
                Messung::create($data);
                $message = 'Messung wurde erstellt.';
            }

            $this->closeMessungModal();
            session()->flash('success', $message);
            
        } catch (\Exception $e) {
            $this->modalError = 'Fehler beim Speichern: ' . $e->getMessage();
        }
    }

    // ========== Properties ==========

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

        // Export-Status: wirkt über Messungen im filterJahr
        if ($this->filterExportStatus === '1') {
            $query->whereHas('messungen', function ($q) {
                $q->whereRaw("RIGHT(cMIS_DATA, 4) = ?", [$this->filterJahr])
                  ->whereNotNull('exported_at');
            });
        } elseif ($this->filterExportStatus === '0') {
            $query->whereHas('messungen', function ($q) {
                $q->whereRaw("RIGHT(cMIS_DATA, 4) = ?", [$this->filterJahr])
                  ->whereNull('exported_at');
            });
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
            'brennstoffe' => self::BRENNSTOFFE,
        ]);
    }
}
