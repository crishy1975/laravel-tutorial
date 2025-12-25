<?php

namespace App\Http\Controllers;

use App\Models\Gebaeude;
use App\Services\FaelligkeitsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FaelligkeitsSimulatorController extends Controller
{
    public function __construct(
        protected FaelligkeitsService $service
    ) {}

    /**
     * Simulator-Seite anzeigen
     */
    public function index()
    {
        // Beispiel-Szenarien
        $beispiele = [
            [
                'name' => 'Halbjährlich (Feb+Aug), Reinigung Ende 2025',
                'monate' => [2, 8],
                'reinigung' => '31.12.2025',
                'stichtag' => '25.12.2025',
                'erwartet' => 'Nicht fällig (nächste: 01.02.2026)',
            ],
            [
                'name' => 'Halbjährlich (Feb+Aug), Reinigung Anfang Feb',
                'monate' => [2, 8],
                'reinigung' => '03.02.2026',
                'stichtag' => '15.02.2026',
                'erwartet' => 'Nicht fällig (nächste: 01.08.2026)',
            ],
            [
                'name' => 'Keine Monate, Reinigung Ende 2025',
                'monate' => [],
                'reinigung' => '31.12.2025',
                'stichtag' => '02.01.2026',
                'erwartet' => 'Fällig seit 01.01.2026',
            ],
            [
                'name' => 'Quartalsweise, keine Reinigung',
                'monate' => [1, 4, 7, 10],
                'reinigung' => null,
                'stichtag' => '15.05.2026',
                'erwartet' => 'Fällig',
            ],
            [
                'name' => 'Monatlich, Reinigung vor 2 Monaten',
                'monate' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                'reinigung' => '15.10.2025',
                'stichtag' => '25.12.2025',
                'erwartet' => 'Fällig seit 01.11.2025 oder 01.12.2025',
            ],
        ];
        
        // Alle Gebäude für Dropdown
        $gebaeude = Gebaeude::orderBy('codex')
            ->get(['id', 'codex', 'gebaeude_name', 'm01', 'm02', 'm03', 'm04', 'm05', 'm06', 'm07', 'm08', 'm09', 'm10', 'm11', 'm12']);
        
        return view('faelligkeit.simulator', compact('beispiele', 'gebaeude'));
    }

    /**
     * Simulation durchführen (AJAX)
     */
    public function simuliere(Request $request)
    {
        $data = $request->validate([
            'monate' => ['nullable', 'array'],
            'monate.*' => ['integer', 'min:1', 'max:12'],
            'reinigung' => ['nullable', 'date'],
            'stichtag' => ['nullable', 'date'],
        ]);
        
        $monate = $data['monate'] ?? [];
        $reinigung = isset($data['reinigung']) ? Carbon::parse($data['reinigung']) : null;
        $stichtag = isset($data['stichtag']) ? Carbon::parse($data['stichtag']) : now();
        
        $result = $this->service->simuliere($monate, $reinigung, $stichtag);
        
        return response()->json($result);
    }

    /**
     * Echtes Gebäude prüfen (AJAX)
     */
    public function pruefeGebaeude(Request $request)
    {
        $data = $request->validate([
            'gebaeude_id' => ['required', 'integer', 'exists:gebaeude,id'],
            'stichtag' => ['nullable', 'date'],
        ]);
        
        $gebaeude = Gebaeude::findOrFail($data['gebaeude_id']);
        $stichtag = isset($data['stichtag']) ? Carbon::parse($data['stichtag']) : now();
        
        $aktiveMonate = $this->service->getAktiveMonate($gebaeude);
        $letzteReinigung = $this->service->getLetzteReinigung($gebaeude);
        
        $result = $this->service->simuliere($aktiveMonate, $letzteReinigung, $stichtag);
        
        // Gebäude-Infos hinzufügen
        $result['gebaeude'] = [
            'id' => $gebaeude->id,
            'codex' => $gebaeude->codex,
            'name' => $gebaeude->gebaeude_name,
            'aktuell_faellig_flag' => (bool) $gebaeude->faellig,
        ];
        
        return response()->json($result);
    }

    /**
     * Batch-Update durchführen
     */
    public function batchUpdate(Request $request)
    {
        $data = $request->validate([
            'stichtag' => ['nullable', 'date'],
        ]);
        
        $stichtag = isset($data['stichtag']) ? Carbon::parse($data['stichtag']) : now();
        
        $stats = $this->service->aktualisiereAlle($stichtag);
        
        return response()->json([
            'ok' => true,
            'message' => "Aktualisierung abgeschlossen: {$stats['geaendert']} von {$stats['gesamt']} Gebäude geändert.",
            'stats' => $stats,
        ]);
    }

    /**
     * Einzelnes Gebäude aktualisieren
     */
    public function updateGebaeude(Request $request, int $id)
    {
        $gebaeude = Gebaeude::findOrFail($id);
        
        $stichtag = $request->has('stichtag') 
            ? Carbon::parse($request->input('stichtag')) 
            : now();
        
        $result = $this->service->aktualisiereGebaeude($gebaeude, $stichtag);
        
        return response()->json([
            'ok' => true,
            'message' => $result['geaendert'] ? 'Gebäude wurde aktualisiert.' : 'Keine Änderung nötig.',
            'result' => $result,
        ]);
    }
}
