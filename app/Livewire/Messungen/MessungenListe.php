<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Messung;
use App\Models\Impianto;
use App\Models\Adresse;
use App\Services\GrenzwertService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\ProtokollController;

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
        'cMIS_T_LIQ_CONV' => '60',
        'cMIS_PERD_FUMI' => '',
        'cMIS_IND_OPACITA' => '0',
        'cMIS_TRACCE_OLEO' => '1',
    ];
    public $grenzwerte = null;

    // Selektion für Email-Versand
    public $selectedMessungen = [];
    public $selectAll = false;

    // Modal: Email versenden
    public $showEmailModal = false;
    public $emailEmpfaenger = [];      // [{email, name}]
    public $emailSearch = '';
    public $emailSuggestions = [];
    public $emailBetreff = '';
    public $emailText = '';
    public $emailError = null;
    public $emailSuccess = null;

    // Neue Adresse anlegen
    public $showNewAdresseForm = false;
    public $newAdresseEmail = '';
    public $newAdresseName = '';

    // Modal: WhatsApp versenden
    public $showWhatsappModal = false;
    public $waSearch = '';
    public $waSuggestions = [];
    public $waNummer = '';
    public $waName = '';
    public $waText = '';

    // Neuen Kontakt anlegen (WhatsApp)
    public $showNewKontaktForm = false;
    public $newKontaktName = '';
    public $newKontaktNummer = '';
    public $newKontaktEmail = '';

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
        // Filter aus Session wiederherstellen (nur wenn keine URL-Parameter)
        if (!request()->hasAny([
            'filterKodex',
            'filterName',
            'filterDatumVon',
            'filterDatumBis',
            'filterErgebnis',
            'filterBrennstoff',
            'filterOhneAnlage',
            'filterJahr',
            'page',
        ])) {
            $saved = session('messungen_filter', []);
            if (!empty($saved)) {
                $this->filterKodex = $saved['filterKodex'] ?? '';
                $this->filterName = $saved['filterName'] ?? '';
                $this->filterDatumVon = $saved['filterDatumVon'] ?? '';
                $this->filterDatumBis = $saved['filterDatumBis'] ?? '';
                $this->filterErgebnis = $saved['filterErgebnis'] ?? '';
                $this->filterBrennstoff = $saved['filterBrennstoff'] ?? '';
                $this->filterOhneAnlage = $saved['filterOhneAnlage'] ?? '';
                $this->filterJahr = $saved['filterJahr'] ?? date('Y');
                $this->sortField = $saved['sortField'] ?? 'cMIS_DATA';
                $this->sortDirection = $saved['sortDirection'] ?? 'desc';
            } else {
                $this->filterJahr = date('Y');
            }
        } else {
            $this->filterJahr = $this->filterJahr ?: date('Y');
        }
    }

    /**
     * Filter in Session speichern
     */
    private function saveToSession(): void
    {
        session(['messungen_filter' => [
            'filterKodex' => $this->filterKodex,
            'filterName' => $this->filterName,
            'filterDatumVon' => $this->filterDatumVon,
            'filterDatumBis' => $this->filterDatumBis,
            'filterErgebnis' => $this->filterErgebnis,
            'filterBrennstoff' => $this->filterBrennstoff,
            'filterOhneAnlage' => $this->filterOhneAnlage,
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

    public function updatingFilterKodex()    { }
    public function updatingFilterName()     { }
    public function updatingFilterDatumVon() { }
    public function updatingFilterDatumBis() { }
    public function updatingFilterErgebnis() { }
    public function updatingFilterBrennstoff() { }
    public function updatingFilterOhneAnlage() { }
    public function updatingFilterJahr()     { }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        // Defensiv: sortDirection immer auf Whitelist normalisieren
        $this->sortDirection = in_array($this->sortDirection, ['asc', 'desc'], true)
            ? $this->sortDirection
            : 'asc';
        $this->saveToSession();
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
        $this->saveToSession();
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
        $kodexListe = $anlagen->pluck('Feld_a')->map(fn($k) => trim($k))->toArray();
        $zugeordneteKodex = Messung::whereIn('cIM_CODICE', $kodexListe)
            ->where('codeInImpianti', '>', 0)
            ->pluck('cIM_CODICE')
            ->map(fn($k) => trim($k))
            ->unique()
            ->toArray();

        // Flag setzen für jede Anlage
        foreach ($anlagen as $anlage) {
            $anlage->hatMessung = in_array(trim($anlage->Feld_a), $zugeordneteKodex);
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
            'cMIS_DATA2' => now('Europe/Rome')->format('d.m.Y'),
            'cMIS_ORA' => now('Europe/Rome')->format('H:i'),
            'cMIS_COMBUSTIBILE' => 'FUEL_NAT_GAS',
            'boilerYear' => '',
            'boilerPower' => '',
            'cMIS_OSSIGENO' => '',
            'cMIS_ANIDRIDE_CARBONICA' => '',
            'cMIS_MONOSSSIDO' => '',
            'cMIS_BIOSSIDO_AZOTO' => '',
            'cMIS_T_GAS_COMB' => '',
            'cMIS_T_ARIA_COMB' => '',
            'cMIS_T_LIQ_CONV' => '60',
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
        // Ohne Baujahr + Leistung sind die Grenzwerte nicht aussagekräftig
        // (würden sonst mit Defaults baujahr=2000, leistung=0 berechnet).
        $boilerYearRaw = $this->messung['boilerYear'] ?? '';
        $boilerPowerRaw = $this->messung['boilerPower'] ?? '';
        if ($boilerYearRaw === '' || $boilerYearRaw === null
            || $boilerPowerRaw === '' || $boilerPowerRaw === null) {
            $this->grenzwerte = null;
            return;
        }

        $brennstoff = $this->messung['cMIS_COMBUSTIBILE'] ?? 'FUEL_NAT_GAS';
        $leistung = (float) $boilerPowerRaw;
        $baujahr = (int) $boilerYearRaw;
        $co = (int) ($this->messung['cMIS_MONOSSSIDO'] ?? 0);
        $nox = (int) ($this->messung['cMIS_BIOSSIDO_AZOTO'] ?? 0);
        $russ = (int) ($this->messung['cMIS_IND_OPACITA'] ?? 0);
        $oelspuren = ($this->messung['cMIS_TRACCE_OLEO'] ?? '1') === '0';

        $service = new GrenzwertService();
        $service->pruefeGrenzwerte($brennstoff, $baujahr, $leistung, $co, $nox, $russ, $oelspuren);
        $result = $service->getResult();

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

        // Amt-Validierung: alle Messfelder müssen vollständig und im Bereich sein
        $formErrors = app(\App\Services\AmtExportService::class)->validateForForm($this->messung);
        if (!empty($formErrors)) {
            $this->modalError = 'Daten unvollständig oder außerhalb der Bereiche. Speichern nicht möglich.';
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

    // ========== Selektion & Email ==========

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            // Alle IDs der aktuellen Seite selektieren
            $this->selectedMessungen = $this->messungen->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedMessungen = [];
        }
    }

    public function updatedSelectedMessungen()
    {
        // selectAll-Status synchronisieren
        $pageIds = $this->messungen->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->selectAll = !empty($pageIds) && empty(array_diff($pageIds, $this->selectedMessungen));
    }

    public function openEmailModal()
    {
        if (empty($this->selectedMessungen)) {
            session()->flash('error', 'Bitte mindestens eine Messung auswählen.');
            return;
        }

        $anzahl = count($this->selectedMessungen);
        $this->emailBetreff = "Messprotokolle ({$anzahl} " . ($anzahl === 1 ? 'Messung' : 'Messungen') . ")";
        $this->emailText = '';
        $this->emailEmpfaenger = [];
        $this->emailSearch = '';
        $this->emailSuggestions = [];
        $this->emailError = null;
        $this->emailSuccess = null;
        $this->showEmailModal = true;
    }

    public function closeEmailModal()
    {
        $this->showEmailModal = false;
        $this->emailEmpfaenger = [];
        $this->emailSearch = '';
        $this->emailSuggestions = [];
        $this->emailBetreff = '';
        $this->emailText = '';
        $this->emailError = null;
        $this->emailSuccess = null;
        $this->showNewAdresseForm = false;
        $this->newAdresseEmail = '';
        $this->newAdresseName = '';
    }

    public function updatedEmailSearch()
    {
        $search = trim($this->emailSearch);
        if (strlen($search) < 2) {
            $this->emailSuggestions = [];
            return;
        }

        $this->emailSuggestions = Adresse::where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('email_zweit', 'like', "%{$search}%")
                  ->orWhere('pec', 'like', "%{$search}%");
            })
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'email' => $a->email,
                'email_zweit' => $a->email_zweit,
                'pec' => $a->pec,
            ])
            ->toArray();
    }

    public function selectEmailAdresse($adresseId, $emailField = 'email')
    {
        $adresse = Adresse::find($adresseId);
        if (!$adresse) return;

        $email = match($emailField) {
            'email_zweit' => $adresse->email_zweit,
            'pec' => $adresse->pec,
            default => $adresse->email,
        };

        if (!$email) return;

        // Doppelte vermeiden
        $exists = collect($this->emailEmpfaenger)->contains(fn($e) => $e['email'] === $email);
        if (!$exists) {
            $this->emailEmpfaenger[] = [
                'email' => $email,
                'name' => $adresse->name,
            ];
        }

        $this->emailSearch = '';
        $this->emailSuggestions = [];
    }

    public function addManualEmail()
    {
        $email = trim($this->emailSearch);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        // Schon als Empfänger hinzugefügt?
        $alreadyAdded = collect($this->emailEmpfaenger)->contains(fn($e) => $e['email'] === $email);
        if ($alreadyAdded) {
            $this->emailSearch = '';
            $this->emailSuggestions = [];
            return;
        }

        // Existiert in der Datenbank?
        $adresse = Adresse::where('email', $email)
            ->orWhere('email_zweit', $email)
            ->orWhere('pec', $email)
            ->first();

        if ($adresse) {
            // Bekannte Adresse → direkt hinzufügen
            $this->emailEmpfaenger[] = [
                'email' => $email,
                'name' => $adresse->name,
            ];
            $this->emailSearch = '';
            $this->emailSuggestions = [];
        } else {
            // Unbekannte Email → Formular für Name anzeigen
            $this->newAdresseEmail = $email;
            $this->newAdresseName = '';
            $this->showNewAdresseForm = true;
            $this->emailSuggestions = [];
        }
    }

    public function saveNewAdresse()
    {
        $email = trim($this->newAdresseEmail);
        $name = trim($this->newAdresseName);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        if (empty($name)) {
            return;
        }

        // In Datenbank speichern (leere Strings für NOT NULL Felder)
        Adresse::create([
            'name' => $name,
            'email' => $email,
            'strasse' => '',
            'hausnummer' => '',
            'plz' => '',
            'wohnort' => '',
            'provinz' => 'BZ',
            'land' => 'IT',
        ]);

        // Als Empfänger hinzufügen
        $exists = collect($this->emailEmpfaenger)->contains(fn($e) => $e['email'] === $email);
        if (!$exists) {
            $this->emailEmpfaenger[] = [
                'email' => $email,
                'name' => $name,
            ];
        }

        // Formular zurücksetzen
        $this->showNewAdresseForm = false;
        $this->newAdresseEmail = '';
        $this->newAdresseName = '';
        $this->emailSearch = '';
    }

    public function cancelNewAdresse()
    {
        $this->showNewAdresseForm = false;
        $this->newAdresseEmail = '';
        $this->newAdresseName = '';
    }

    public function removeEmailEmpfaenger($index)
    {
        unset($this->emailEmpfaenger[$index]);
        $this->emailEmpfaenger = array_values($this->emailEmpfaenger);
    }

    public function sendEmail()
    {
        $this->emailError = null;
        $this->emailSuccess = null;

        if (empty($this->emailEmpfaenger)) {
            $this->emailError = 'Bitte mindestens einen Empfänger angeben.';
            return;
        }

        if (empty($this->emailBetreff)) {
            $this->emailError = 'Betreff ist erforderlich.';
            return;
        }

        try {
            $messungen = Messung::whereIn('id', $this->selectedMessungen)->get();
            $protokollController = new ProtokollController();

            // PDFs erzeugen
            $attachments = [];
            foreach ($messungen as $m) {
                try {
                    $pdfString = $protokollController->generatePdfString($m);
                    $filename = ProtokollController::getFilename($m);
                    $attachments[] = [
                        'data' => $pdfString,
                        'name' => $filename,
                    ];
                } catch (\Exception $e) {
                    $attachments[] = [
                        'data' => null,
                        'name' => null,
                        'error' => "Protokoll für {$m->cIM_CODICE} konnte nicht erzeugt werden: {$e->getMessage()}",
                    ];
                }
            }

            // Fehlerhafte PDFs sammeln
            $errors = collect($attachments)->filter(fn($a) => isset($a['error']))->pluck('error')->toArray();
            $validAttachments = collect($attachments)->filter(fn($a) => !isset($a['error']))->values()->toArray();

            if (empty($validAttachments)) {
                $this->emailError = 'Kein Protokoll konnte erzeugt werden. ' . implode(' ', $errors);
                return;
            }

            // HTML-Email rendern
            $htmlBody = view('emails.messungen-protokolle', [
                'messungen' => $messungen,
                'anzahlProtokolle' => count($validAttachments),
                'nachricht' => $this->emailText,
                'fehler' => $errors,
            ])->render();

            $recipients = collect($this->emailEmpfaenger)->pluck('email')->toArray();
            $betreff = $this->emailBetreff;

            Mail::send([], [], function ($message) use ($recipients, $betreff, $htmlBody, $validAttachments) {
                $message->to($recipients)
                        ->subject($betreff)
                        ->html($htmlBody);

                foreach ($validAttachments as $att) {
                    $message->attachData($att['data'], $att['name'], [
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            $anzahl = count($recipients);
            $this->selectedMessungen = [];
            $this->selectAll = false;

            session()->flash('success', count($validAttachments) . " Protokoll(e) an {$anzahl} Empfänger gesendet.");
            $this->closeEmailModal();

        } catch (\Exception $e) {
            $this->emailError = 'Fehler beim Senden: ' . $e->getMessage();
        }
    }

    // ========== WhatsApp ==========

    public function openWhatsappModal()
    {
        if (empty($this->selectedMessungen)) {
            session()->flash('error', 'Bitte mindestens eine Messung auswählen.');
            return;
        }

        $messungen = Messung::whereIn('id', $this->selectedMessungen)->get();
        $protokollController = new ProtokollController();
        $baseUrl = rtrim(config('app.url'), '/');

        // PDFs erzeugen und im Storage speichern
        $text = "📋 *Messprotokolle Resch GmbH*\n\n";
        $errors = [];

        foreach ($messungen as $m) {
            $ergebnis = $m->strEsito === '1' ? '✅ Positiv' : ($m->strEsito === '0' ? '❌ Negativ' : '─');
            $datum = $m->cMIS_DATA2 ?: '';

            try {
                $pdfString = $protokollController->generatePdfString($m);
                $token = Str::random(8);
                $filename = ProtokollController::getFilename($m);
                $storagePath = "protokolle/{$token}_{$filename}";

                Storage::disk('local')->put($storagePath, $pdfString);

                $text .= "🔥 *Abgaskontrolle vom {$datum}*\n";
                $text .= "{$m->cIM_NAME} (Kodex {$m->cIM_CODICE}) | {$ergebnis}\n";
                $text .= "👉 {$baseUrl}/p/{$token}\n\n";
            } catch (\Exception $e) {
                $errors[] = "PDF für {$m->cIM_CODICE}: {$e->getMessage()}";
                $text .= "🔥 {$m->cIM_CODICE} – {$m->cIM_NAME}\n";
                $text .= "⚠️ _PDF konnte nicht erstellt werden_\n\n";
            }
        }

        $text .= "─────────────────\n";
        $text .= "Resch GmbH – Kaminkehrer\n";
        $text .= "📞 338 4693481\n";
        $text .= "✉️ info@resch.bz";

        $this->waText = $text;
        $this->waNummer = '';
        $this->waName = '';
        $this->waSearch = '';
        $this->waSuggestions = [];
        $this->showWhatsappModal = true;

        if (!empty($errors)) {
            session()->flash('error', implode(' | ', $errors));
        }
    }

    public function closeWhatsappModal()
    {
        $this->showWhatsappModal = false;
        $this->waSearch = '';
        $this->waSuggestions = [];
        $this->waNummer = '';
        $this->waName = '';
        $this->waText = '';
        $this->showNewKontaktForm = false;
        $this->newKontaktName = '';
        $this->newKontaktNummer = '';
        $this->newKontaktEmail = '';
    }

    public function updatedWaSearch()
    {
        $search = trim($this->waSearch);
        if (strlen($search) < 2) {
            $this->waSuggestions = [];
            return;
        }

        $this->waSuggestions = Adresse::where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('handy', 'like', "%{$search}%")
                  ->orWhere('telefon', 'like', "%{$search}%");
            })
            ->where(function ($q) {
                $q->whereNotNull('handy')->where('handy', '!=', '')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('telefon')->where('telefon', '!=', '');
                  });
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'handy' => $a->handy,
                'telefon' => $a->telefon,
            ])
            ->toArray();
    }

    public function selectWaNummer($adresseId, $field = 'handy')
    {
        $adresse = Adresse::find($adresseId);
        if (!$adresse) return;

        $nummer = $field === 'telefon' ? $adresse->telefon : $adresse->handy;
        if (!$nummer) return;

        $this->waNummer = $nummer;
        $this->waName = $adresse->name;
        $this->waSearch = '';
        $this->waSuggestions = [];
    }

    public function addManualWaNummer()
    {
        $input = trim($this->waSearch);
        if (empty($input)) return;

        $digits = preg_replace('/[^0-9]/', '', $input);
        if (strlen($digits) < 6) return;

        // Existiert in der Datenbank?
        $adresse = Adresse::where('handy', 'like', "%{$digits}%")
            ->orWhere('telefon', 'like', "%{$digits}%")
            ->first();

        if ($adresse) {
            $this->waNummer = $adresse->handy ?: $adresse->telefon;
            $this->waName = $adresse->name;
            $this->waSearch = '';
            $this->waSuggestions = [];
        } else {
            // Unbekannte Nummer → Formular anzeigen
            $this->newKontaktNummer = $input;
            $this->newKontaktName = '';
            $this->newKontaktEmail = '';
            $this->showNewKontaktForm = true;
            $this->waSuggestions = [];
        }
    }

    public function saveNewKontakt()
    {
        $name = trim($this->newKontaktName);
        $nummer = trim($this->newKontaktNummer);
        $email = trim($this->newKontaktEmail);

        if (empty($name) || empty($nummer)) return;

        // In Datenbank speichern
        Adresse::create([
            'name' => $name,
            'handy' => $nummer,
            'email' => $email ?: '',
            'strasse' => '',
            'hausnummer' => '',
            'plz' => '',
            'wohnort' => '',
            'provinz' => 'BZ',
            'land' => 'IT',
        ]);

        // Als Empfänger setzen
        $this->waNummer = $nummer;
        $this->waName = $name;
        $this->waSearch = '';
        $this->showNewKontaktForm = false;
        $this->newKontaktName = '';
        $this->newKontaktNummer = '';
        $this->newKontaktEmail = '';
    }

    public function cancelNewKontakt()
    {
        $this->showNewKontaktForm = false;
        $this->newKontaktName = '';
        $this->newKontaktNummer = '';
        $this->newKontaktEmail = '';
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
        // Whitelist für Richtung, da unten via orderByRaw interpoliert
        $dir = in_array($this->sortDirection, ['asc', 'desc'], true) ? $this->sortDirection : 'asc';
        if ($this->sortField === 'cMIS_DATA') {
            $query->orderByRaw("STR_TO_DATE(cMIS_DATA, '%d%m%Y') {$dir}");
        } else {
            $query->orderBy($this->sortField, $dir);
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

    /**
     * Live-Validierung der Eingaben im Messung-Modal.
     * Leeres Array = alles OK → Speichern-Button aktiv.
     */
    public function getFormErrorsProperty(): array
    {
        if (!$this->showMessungModal || empty($this->messung)) {
            return [];
        }
        return app(\App\Services\AmtExportService::class)->validateForForm($this->messung);
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
            'formErrors' => $this->formErrors,
        ]);
    }
}
