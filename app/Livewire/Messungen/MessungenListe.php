<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Messung;
use App\Models\Impianto;
use App\Services\GrenzwertService;

#[Layout('layouts.app')]
class MessungenListe extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filter
    public $filterKodex = '';
    public $filterName = '';
    public $filterDatumVon = '';
    public $filterDatumBis = '';
    public $filterErgebnis = '';
    public $filterBrennstoff = '';
    public $filterOhneAnlage = '';
    public $filterJahr;

    // Sortierung
    public $sortField = 'cMIS_DATA';
    public $sortDirection = 'desc';

    // Modal: Anlage zuordnen
    public $showAnlageModal = false;
    public $selectedMessungId = null;
    public $selectedMessung = null;
    public $anlageSearchOrt = '';
    public $anlageSearchStrasse = '';
    public $anlageSearchNummer = '';
    public $anlageSearchName = '';
    public $anlageSearchResults = [];
    public $anlageSearchPage = 1;
    public $anlageSearchTotal = 0;

    // Modal: Neue/Bearbeiten Messung
    public $showMessungModal = false;
    public $editMessungId = null;
    public $modalError = null;
    public $messung = [
        'cIM_CODICE' => '',
        'cIM_NAME' => '',
        'cMIS_STADIO' => '1',
        'cMIS_DATA2' => '',
        'cMIS_ORA' => '',
        'cMIS_COMBUSTIBILE' => 'FUEL_NAT_GAS',
        'boilerYear' => '',
        'boilerPower' => '',
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

    protected $queryString = [
        'filterKodex' => ['except' => ''],
        'filterName' => ['except' => ''],
        'filterDatumVon' => ['except' => ''],
        'filterDatumBis' => ['except' => ''],
        'filterErgebnis' => ['except' => ''],
        'filterBrennstoff' => ['except' => ''],
        'filterOhneAnlage' => ['except' => ''],
        'filterJahr' => ['except' => ''],
    ];

    public function mount()
    {
        $this->filterJahr = date('Y');
    }

    public function updatingFilterKodex()
    {
        $this->resetPage();
    }
    public function updatingFilterName()
    {
        $this->resetPage();
    }
    public function updatingFilterDatumVon()
    {
        $this->resetPage();
    }
    public function updatingFilterDatumBis()
    {
        $this->resetPage();
    }
    public function updatingFilterErgebnis()
    {
        $this->resetPage();
    }
    public function updatingFilterBrennstoff()
    {
        $this->resetPage();
    }
    public function updatingFilterOhneAnlage()
    {
        $this->resetPage();
    }
    public function updatingFilterJahr()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->filterKodex = '';
        $this->filterName = '';
        $this->filterDatumVon = '';
        $this->filterDatumBis = '';
        $this->filterErgebnis = '';
        $this->filterBrennstoff = '';
        $this->filterOhneAnlage = '';
        $this->filterJahr = date('Y');
        $this->resetPage();
    }

    // ========== Modal: Anlage zuordnen ==========

    public function openAnlageModal($messungId)
    {
        $this->selectedMessungId = $messungId;
        $this->selectedMessung = Messung::find($messungId);
        $this->resetAnlageSearch();
        $this->showAnlageModal = true;
    }

    public function closeAnlageModal()
    {
        $this->showAnlageModal = false;
        $this->selectedMessungId = null;
        $this->selectedMessung = null;
        $this->resetAnlageSearch();
    }

    public function resetAnlageSearch()
    {
        $this->anlageSearchOrt = '';
        $this->anlageSearchStrasse = '';
        $this->anlageSearchNummer = '';
        $this->anlageSearchName = '';
        $this->anlageSearchResults = [];
        $this->anlageSearchPage = 1;
        $this->anlageSearchTotal = 0;
    }

    public function searchAnlagen()
    {
        $this->anlageSearchPage = 1;
        $this->loadAnlagen();
    }

    public function anlagePagePrev()
    {
        if ($this->anlageSearchPage > 1) {
            $this->anlageSearchPage--;
            $this->loadAnlagen();
        }
    }

    public function anlagePageNext()
    {
        $maxPage = ceil($this->anlageSearchTotal / 10);
        if ($this->anlageSearchPage < $maxPage) {
            $this->anlageSearchPage++;
            $this->loadAnlagen();
        }
    }

    public function anlageGoToPage($page)
    {
        $maxPage = ceil($this->anlageSearchTotal / 10);
        if ($page >= 1 && $page <= $maxPage) {
            $this->anlageSearchPage = $page;
            $this->loadAnlagen();
        }
    }

    protected function loadAnlagen()
    {
        $query = Impianto::query();

        // Mindestens ein Suchkriterium erforderlich
        $hasFilter = false;

        // Aufstellungsort/Name (Feld_w)
        if ($this->anlageSearchName) {
            $query->where('Feld_w', 'like', "%{$this->anlageSearchName}%");
            $hasFilter = true;
        }

        // Gemeinde/Ort (Feld_i = DE, Feld_h = IT)
        if ($this->anlageSearchOrt) {
            $query->where(function ($q) {
                $q->where('Feld_i', 'like', "%{$this->anlageSearchOrt}%")
                    ->orWhere('Feld_h', 'like', "%{$this->anlageSearchOrt}%");
            });
            $hasFilter = true;
        }

        // Straße (Feld_m = DE, Feld_l = IT)
        if ($this->anlageSearchStrasse) {
            $query->where(function ($q) {
                $q->where('Feld_m', 'like', "%{$this->anlageSearchStrasse}%")
                    ->orWhere('Feld_l', 'like', "%{$this->anlageSearchStrasse}%");
            });
            $hasFilter = true;
        }

        // Hausnummer (Feld_n)
        if ($this->anlageSearchNummer) {
            $query->where('Feld_n', 'like', "%{$this->anlageSearchNummer}%");
            $hasFilter = true;
        }

        if (!$hasFilter) {
            $this->anlageSearchResults = [];
            $this->anlageSearchTotal = 0;
            return;
        }

        // Gesamtanzahl
        $this->anlageSearchTotal = (clone $query)->count();

        // Sortierung: Gemeinde, Straße, Hausnummer (numerisch!)
        $query->orderBy('Feld_i', 'asc')
            ->orderBy('Feld_m', 'asc')
            ->orderByRaw('CAST(Feld_n AS UNSIGNED) ASC')
            ->orderBy('Feld_n', 'asc'); // Fallback für nicht-numerische

        // Pagination: 10 pro Seite
        $offset = ($this->anlageSearchPage - 1) * 10;
        $anlagen = $query->skip($offset)->take(10)->get();

        // Prüfen welche Anlagen bereits Messungen haben
        $kodexListe = $anlagen->pluck('Feld_a')->toArray();
        $zugeordneteKodex = Messung::whereIn('cIM_CODICE', $kodexListe)
            ->where('codeInImpianti', '>', 0)
            ->pluck('cIM_CODICE')
            ->unique()
            ->toArray();

        // Flag setzen für jede Anlage
        foreach ($anlagen as $anlage) {
            $anlage->hatMessung = in_array($anlage->Feld_a, $zugeordneteKodex);
        }

        $this->anlageSearchResults = $anlagen;
    }

    public function zuordnenAnlage($anlageKodex)
    {
        if (!$this->selectedMessungId) {
            return;
        }

        $messung = Messung::find($this->selectedMessungId);
        if (!$messung) {
            return;
        }

        // Anlage laden für zusätzliche Infos
        $anlage = Impianto::where('Feld_a', $anlageKodex)->first();

        // Messung aktualisieren
        $messung->cIM_CODICE = $anlageKodex;
        $messung->codeInImpianti = 1;

        // Optional: Baujahr und Leistung von Anlage übernehmen wenn leer
        if (empty($messung->boilerYear) && $anlage && $anlage->Feld_z) {
            $messung->boilerYear = $anlage->Feld_z;
        }
        if (empty($messung->boilerPower) && $anlage && $anlage->Feld_ab) {
            $messung->boilerPower = $anlage->Feld_ab;
        }

        $messung->save();

        $this->closeAnlageModal();

        session()->flash('success', "Messung wurde der Anlage {$anlageKodex} zugeordnet.");
    }

    // ========== Modal: Neue/Bearbeiten Messung ==========

    public function openMessungModal()
    {
        // Nächsten freien Kodex vorschlagen (höchster numerischer Kodex + 1)
        $letzterKodex = Messung::where('cIM_CODICE', 'REGEXP', '^[0-9]+$')
            ->orderByRaw('CAST(cIM_CODICE AS UNSIGNED) DESC')
            ->value('cIM_CODICE');
        $neuerKodex = $letzterKodex ? (string)((int)$letzterKodex + 1) : '1';

        $this->editMessungId = null;
        $this->messung = [
            'cIM_CODICE' => $neuerKodex,
            'cIM_NAME' => '',
            'cMIS_STADIO' => '1',
            'cMIS_DATA2' => date('d.m.Y'),
            'cMIS_ORA' => date('H:i'),
            'cMIS_COMBUSTIBILE' => 'FUEL_NAT_GAS',
            'boilerYear' => '',
            'boilerPower' => '',
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
        $this->modalError = null;
        $this->showMessungModal = true;
    }

    public function editMessung($id)
    {
        $m = Messung::findOrFail($id);

        $this->editMessungId = $id;
        $this->messung = [
            'cIM_CODICE' => $m->cIM_CODICE ?? '',
            'cIM_NAME' => $m->cIM_NAME ?? '',
            'cMIS_STADIO' => $m->cMIS_STADIO ?? '1',
            'cMIS_DATA2' => $m->cMIS_DATA2 ?? '',
            'cMIS_ORA' => $m->cMIS_ORA ?? '',
            'cMIS_COMBUSTIBILE' => $m->cMIS_COMBUSTIBILE ?? 'FUEL_NAT_GAS',
            'boilerYear' => $m->boilerYear ?? '',
            'boilerPower' => $m->boilerPower ?? '',
            'cMIS_OSSIGENO' => $m->cMIS_OSSIGENO ?? '',
            'cMIS_ANIDRIDE_CARBONICA' => $m->cMIS_ANIDRIDE_CARBONICA ?? '',
            'cMIS_MONOSSSIDO' => $m->cMIS_MONOSSSIDO ?? '',
            'cMIS_BIOSSIDO_AZOTO' => $m->cMIS_BIOSSIDO_AZOTO ?? '',
            'cMIS_T_GAS_COMB' => $m->cMIS_T_GAS_COMB ?? '',
            'cMIS_T_ARIA_COMB' => $m->cMIS_T_ARIA_COMB ?? '',
            'cMIS_T_LIQ_CONV' => $m->cMIS_T_LIQ_CONV ?? '',
            'cMIS_PERD_FUMI' => $m->cMIS_PERD_FUMI ?? '',
            'cMIS_IND_OPACITA' => $m->cMIS_IND_OPACITA ?? '0',
            'cMIS_TRACCE_OLEO' => $m->cMIS_TRACCE_OLEO ?? '1',
        ];

        $this->grenzwerte = null;
        $this->modalError = null;
        $this->berechneGrenzwerte();
        $this->showMessungModal = true;
    }

    public function closeMessungModal()
    {
        $this->showMessungModal = false;
        $this->editMessungId = null;
        $this->messung = [
            'cIM_CODICE' => '',
            'cIM_NAME' => '',
            'cMIS_STADIO' => '1',
            'cMIS_DATA2' => '',
            'cMIS_ORA' => '',
            'cMIS_COMBUSTIBILE' => 'FUEL_NAT_GAS',
            'boilerYear' => '',
            'boilerPower' => '',
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
        $this->modalError = null;
    }

    public function updatedMessung($value, $key)
    {
        // Bei Änderungen an relevanten Messwerten: Grenzwerte neu berechnen
        $relevantKeys = [
            'cMIS_COMBUSTIBILE',
            'cMIS_MONOSSSIDO',
            'cMIS_BIOSSIDO_AZOTO',
            'cMIS_IND_OPACITA',
            'cMIS_TRACCE_OLEO',
            'boilerYear',
            'boilerPower',
        ];

        if (in_array($key, $relevantKeys)) {
            $this->berechneGrenzwerte();
        }
    }

    public function berechneGrenzwerte()
    {
        // Nur wenn relevante Daten vorhanden
        $brennstoff = $this->messung['cMIS_COMBUSTIBILE'] ?? '';
        if (!$brennstoff) {
            $this->grenzwerte = null;
            return;
        }

        $fuelInfo = self::BRENNSTOFFE[$brennstoff] ?? null;
        if (!$fuelInfo) {
            $this->grenzwerte = null;
            return;
        }

        $baujahr = (int)($this->messung['boilerYear'] ?? 0);
        $leistung = (float)($this->messung['boilerPower'] ?? 0);
        $co = (float)($this->messung['cMIS_MONOSSSIDO'] ?? 0);
        $nox = (float)($this->messung['cMIS_BIOSSIDO_AZOTO'] ?? 0);
        $russ = (float)($this->messung['cMIS_IND_OPACITA'] ?? 0);
        $oel = ($this->messung['cMIS_TRACCE_OLEO'] ?? '1') === '0';

        // GrenzwertService verwenden
        $result = GrenzwertService::berechne(
            $fuelInfo['nr'],
            $baujahr,
            $leistung,
            $co,
            $nox,
            $russ,
            $oel
        );

        $this->grenzwerte = [
            'co' => [
                'grenzwert' => $result['maxCO'],
                'status' => $result['coUeberschritten'] ? 'rot' : ($co > $result['maxCO'] * 0.8 ? 'gelb' : 'gruen'),
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

        $this->validate([
            'messung.cIM_CODICE' => 'required|min:1',
            'messung.cIM_NAME' => 'required|min:2',
            'messung.cMIS_STADIO' => 'required',
            'messung.cMIS_DATA2' => 'required',
            'messung.cMIS_COMBUSTIBILE' => 'required',
        ], [
            'messung.cIM_CODICE.required' => 'Kodex ist erforderlich.',
            'messung.cIM_NAME.required' => 'Name ist erforderlich.',
            'messung.cIM_NAME.min' => 'Name muss mindestens 2 Zeichen haben.',
            'messung.cMIS_STADIO.required' => 'Stadio ist erforderlich.',
            'messung.cMIS_DATA2.required' => 'Datum ist erforderlich.',
            'messung.cMIS_COMBUSTIBILE.required' => 'Brennstoff ist erforderlich.',
        ]);

        try {
            // Datum konvertieren
            $datumParts = explode('.', $this->messung['cMIS_DATA2'] ?? '');
            $dateDMY = '';
            if (count($datumParts) === 3) {
                $dateDMY = $datumParts[0] . $datumParts[1] . $datumParts[2];
            }

            // Brennstoff-Info
            $fuelInfo = self::BRENNSTOFFE[$this->messung['cMIS_COMBUSTIBILE'] ?? 'FUEL_NAT_GAS'] ?? self::BRENNSTOFFE['FUEL_NAT_GAS'];

            // Komma zu Punkt konvertieren
            $dezimalFelder = [
                'cMIS_OSSIGENO',
                'cMIS_ANIDRIDE_CARBONICA',
                'cMIS_PERD_FUMI',
                'cMIS_T_GAS_COMB',
                'cMIS_T_ARIA_COMB',
                'cMIS_T_LIQ_CONV'
            ];
            foreach ($dezimalFelder as $feld) {
                if (isset($this->messung[$feld])) {
                    $this->messung[$feld] = str_replace(',', '.', $this->messung[$feld]);
                }
            }

            // Ganzzahl-Felder runden
            $intFelder = ['cMIS_MONOSSSIDO', 'cMIS_BIOSSIDO_AZOTO', 'cMIS_T_GAS_COMB', 'cMIS_T_ARIA_COMB', 'cMIS_T_LIQ_CONV'];
            foreach ($intFelder as $feld) {
                if (isset($this->messung[$feld]) && $this->messung[$feld] !== '') {
                    $this->messung[$feld] = (string) round((float) $this->messung[$feld]);
                }
            }

            // Grenzwerte berechnen für Ergebnis
            $this->berechneGrenzwerte();
            $esito = '1';
            if ($this->grenzwerte) {
                if (($this->grenzwerte['co']['status'] ?? '') === 'rot' ||
                    ($this->grenzwerte['nox']['status'] ?? '') === 'rot' ||
                    ($this->grenzwerte['russ']['status'] ?? '') === 'rot' ||
                    ($this->grenzwerte['oel']['status'] ?? '') === 'rot'
                ) {
                    $esito = '0';
                }
            }

            // Daten vorbereiten
            $data = [
                'cIM_CODICE' => $this->messung['cIM_CODICE'] ?? '',
                'cIM_NAME' => $this->messung['cIM_NAME'] ?? '',
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
                'boilerYear' => $this->messung['boilerYear'] ?? '',
                'boilerPower' => $this->messung['boilerPower'] ?? '',
            ];

            if ($this->editMessungId) {
                // UPDATE bestehende Messung
                $messung = Messung::findOrFail($this->editMessungId);
                $messung->update($data);
                $this->closeMessungModal();
                session()->flash('success', 'Messung wurde aktualisiert.');
            } else {
                // INSERT neue Messung (ohne Anlage = codeInImpianti = 0)
                $data['codeInImpianti'] = 0;
                Messung::create($data);
                $this->closeMessungModal();
                session()->flash('success', 'Messung wurde erstellt.');
            }
        } catch (\Exception $e) {
            $this->modalError = 'Fehler beim Speichern: ' . $e->getMessage();
        }
    }

    // ========== Properties ==========

    public function getMessungenProperty()
    {
        $query = Messung::query();

        // Jahr-Filter (Standard)
        if ($this->filterJahr) {
            $query->imJahr((int) $this->filterJahr);
        }

        // Kodex
        if ($this->filterKodex) {
            $query->where('cIM_CODICE', 'like', "%{$this->filterKodex}%");
        }

        // Name/Kunde
        if ($this->filterName) {
            $query->where('cIM_NAME', 'like', "%{$this->filterName}%");
        }

        // Datum von
        if ($this->filterDatumVon) {
            $datumVon = date('dmY', strtotime($this->filterDatumVon));
            $query->where('cMIS_DATA', '>=', $datumVon);
        }

        // Datum bis
        if ($this->filterDatumBis) {
            $datumBis = date('dmY', strtotime($this->filterDatumBis));
            $query->where('cMIS_DATA', '<=', $datumBis);
        }

        // Ergebnis
        if ($this->filterErgebnis !== '') {
            $query->where('strEsito', $this->filterErgebnis);
        }

        // Brennstoff
        if ($this->filterBrennstoff) {
            $query->where('cMIS_COMBUSTIBILE', $this->filterBrennstoff);
        }

        // Ohne Anlage
        if ($this->filterOhneAnlage === '1') {
            $query->where('codeInImpianti', 0);
        } elseif ($this->filterOhneAnlage === '0') {
            $query->where('codeInImpianti', '>', 0);
        }

        // Sortierung
        // Datum korrekt sortieren (cMIS_DATA ist im Format ddmmyyyy)
        if ($this->sortField === 'cMIS_DATA') {
            $query->orderByRaw("STR_TO_DATE(cMIS_DATA, '%d%m%Y') {$this->sortDirection}");
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        // Sekundäre Sortierung: Stadio aufwärts
        if ($this->sortField !== 'cMIS_STADIO') {
            $query->orderBy('cMIS_STADIO', 'asc');
        }

        return $query->paginate(25);
    }

    public function getStatistikProperty()
    {
        $jahr = (int) $this->filterJahr;

        $query = Messung::query();
        if ($jahr) {
            $query->imJahr($jahr);
        }

        $total = (clone $query)->count();
        $positiv = (clone $query)->positiv()->count();
        $negativ = (clone $query)->negativ()->count();
        $ohneAnlage = (clone $query)->where('codeInImpianti', 0)->count();
        $mitAnlage = $total - $ohneAnlage;

        return [
            'total' => $total,
            'positiv' => $positiv,
            'negativ' => $negativ,
            'ohneAnlage' => $ohneAnlage,
            'mitAnlage' => $mitAnlage,
            // Aliase für Kompatibilität
            'mitMessung' => $mitAnlage,
            'ohneMessung' => $ohneAnlage,
        ];
    }

    public function getBrennstoffeProperty()
    {
        return self::BRENNSTOFFE;
    }

    public function delete($id)
    {
        $messung = Messung::findOrFail($id);
        $messung->delete();

        session()->flash('success', 'Messung wurde gelöscht.');
    }

    public function render()
    {
        return view('livewire.messungen.messungen-liste', [
            'messungen' => $this->messungen,
            'statistik' => $this->statistik,
            'brennstoffe' => $this->brennstoffe,
        ]);
    }
}
