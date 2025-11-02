<?php
// app/Http/Controllers/TourController.php

namespace App\Http\Controllers;

use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TourController extends Controller
{
    /**
     * Kleine Helper-Funktion: wenn returnTo mitkommt, dorthin zurück.
     * Fallback ist eine benannte Route (z. B. tour.index).
     */
    private function redirectToReturnTo(Request $request, string $fallbackRouteName)
    {
        $returnTo = $request->input('returnTo'); // kann aus GET oder POST kommen
        if (is_string($returnTo) && strlen($returnTo) > 0) {
            return redirect()->to($returnTo);
        }
        return redirect()->route($fallbackRouteName);
    }

    /**
     * Liste der Touren mit Suche/Filter.
     * Sortierung: aktive zuerst, dann Reihenfolge, dann ID.
     */
    public function index(Request $request)
    {
        $q     = trim((string) $request->query('q', ''));
        $aktiv = $request->query('aktiv', ''); // '', '1', '0'

        $query = Tour::query();

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('beschreibung', 'like', "%{$q}%");
            });
        }

        if ($aktiv !== '' && ($aktiv === '0' || $aktiv === '1')) {
            $query->where('aktiv', (int)$aktiv);
        }

        $touren = $query
            ->orderBy('aktiv', 'desc')      // aktive (1) vor inaktive (0)
            ->orderBy('reihenfolge', 'asc') // eigene Sortierung
            ->orderBy('id', 'asc')
            ->paginate(50);

        return view('tour.index', compact('touren'));
    }

    /**
     * Detailansicht einer Tour (Name, Beschreibung, verknüpfte Gebäude inkl. Pivot-Reihenfolge).
     */
    public function show(Request $request, int $id)
    {
        $tour = Tour::with(['gebaeude' => function ($q) {
            $q->withPivot('reihenfolge');
        }])->findOrFail($id);

        return view('tour.show', compact('tour'));
    }

    /**
     * Formular: neue Tour anlegen.
     */
    public function create(Request $request)
    {
        return view('tour.create');
    }

    // app/Http/Controllers/TourController.php

    public function detachGebaeude(Request $request, int $id)
    {
        // ✅ Nur die Pivot-Verknüpfung löschen, Gebäude/Tour bleiben bestehen
        $data = $request->validate([
            'ids'      => ['required', 'array', 'min:1'],     // Gebäude-IDs
            'ids.*'    => ['integer', 'exists:gebaeude,id'], // existierende Gebäude
            'returnTo' => ['nullable', 'string'],
        ]);

        $tour = \App\Models\Tour::findOrFail($id);

        // atomar (optional)
        DB::transaction(function () use ($tour, $data) {
            $tour->gebaeude()->detach($data['ids']);
        });

        // zurück (bevorzugt returnTo)
        $back = $data['returnTo'] ?? route('tour.show', $tour->id);
        return redirect()->to($back)->with('success', 'Verknüpfung(en) entfernt.');
    }


    /**
     * Speichert eine neue Tour.
     * - reihenfolge: wenn leer -> max(reihenfolge)+1
     * - aktiv: default 1
     * - Gebäude-Verknüpfungen (Pivot) werden via sync() gespeichert, falls mitgeschickt.
     *
     * Erwartete Request-Formate für Gebäude:
     *  a) gebaeude: [ {id: 12, reihenfolge: 1}, {id: 34, reihenfolge: 2}, ... ]
     *  b) gebaeude_ids: [12,34,...]  (Reihenfolge wird dann 1..N vergeben)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'beschreibung' => ['nullable', 'string'],
            'aktiv'        => ['nullable', 'in:0,1'],
            'reihenfolge'  => ['nullable', 'integer', 'min:1'],
            'returnTo'     => ['nullable', 'string'],

            // Pivot-Eingaben (optional)
            'gebaeude'                 => ['sometimes', 'array'],
            'gebaeude.*.id'            => ['required_with:gebaeude', 'integer', 'exists:gebaeude,id'],
            'gebaeude.*.reihenfolge'   => ['required_with:gebaeude', 'integer', 'min:1'],

            'gebaeude_ids'             => ['sometimes', 'array'],
            'gebaeude_ids.*'           => ['integer', 'exists:gebaeude,id'],
        ]);

        // Reihenfolge automatisch fortlaufend, wenn nicht gesetzt:
        if (!isset($data['reihenfolge']) || $data['reihenfolge'] === null) {
            $data['reihenfolge'] = (int) (Tour::max('reihenfolge') ?? 0) + 1;
        }

        $tour = new Tour();
        $tour->name         = $data['name'];
        $tour->beschreibung = $data['beschreibung'] ?? null;
        $tour->aktiv        = isset($data['aktiv']) ? (int)$data['aktiv'] : 1;
        $tour->reihenfolge  = (int)$data['reihenfolge'];
        $tour->save();

        // ===== Pivot speichern (optional) =====
        // Wir unterstützen beide Formate und normalisieren auf sync-Array: [gebaeude_id => ['reihenfolge' => X], ...]
        $sync = $this->buildGebaeudeSyncArray($request);

        if (!empty($sync)) {
            // sync() setzt genau die gelieferten Einträge, alle anderen werden entfernt.
            // Wenn du vorhandene NICHT löschen willst, nimm: syncWithoutDetaching($sync) und lösche/entferne separat.
            $tour->gebaeude()->sync($sync);
        }

        return $this->redirectToReturnTo($request, 'tour.index')
            ->with('success', 'Tour erfolgreich angelegt.');
    }

    /**
     * Formular: Tour bearbeiten.
     */
    public function edit(Request $request, int $id)
    {
        $tour = Tour::with(['gebaeude' => function ($q) {
            $q->withPivot('reihenfolge');
        }])->findOrFail($id);

        return view('tour.edit', compact('tour'));
    }

    /**
     * Speichert Änderungen an einer Tour inkl. Pivot-Relationen.
     * - Wenn 'gebaeude' / 'gebaeude_ids' mitkommen, werden Pivot-Verknüpfungen per sync() aktualisiert.
     * - Wenn KEINE Gebäude-Infos mitkommen, bleiben bestehende Verknüpfungen unberührt.
     */
    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'beschreibung' => ['nullable', 'string'],
            'aktiv'        => ['nullable', 'in:0,1'],
            'reihenfolge'  => ['required', 'integer', 'min:1'],
            'returnTo'     => ['nullable', 'string'],

            // Pivot-Eingaben (optional)
            'gebaeude'                 => ['sometimes', 'array'],
            'gebaeude.*.id'            => ['required_with:gebaeude', 'integer', 'exists:gebaeude,id'],
            'gebaeude.*.reihenfolge'   => ['required_with:gebaeude', 'integer', 'min:1'],

            'gebaeude_ids'             => ['sometimes', 'array'],
            'gebaeude_ids.*'           => ['integer', 'exists:gebaeude,id'],
        ]);

        $tour = Tour::findOrFail($id);
        $tour->name         = $data['name'];
        $tour->beschreibung = $data['beschreibung'] ?? null;
        $tour->aktiv        = (int)($data['aktiv'] ?? 0);
        $tour->reihenfolge  = (int)$data['reihenfolge'];
        $tour->save();

        // ===== Pivot speichern (nur wenn geliefert) =====
        $sync = $this->buildGebaeudeSyncArray($request);

        if (!empty($sync) || $request->has('gebaeude') || $request->has('gebaeude_ids')) {
            // Wenn explizit geliefert, dann sync() – nicht gelieferte werden entfernt.
            $tour->gebaeude()->sync($sync);
        }
        // Wenn NICHT geliefert, lassen wir die bestehenden Pivot-Daten unverändert.

        return $this->redirectToReturnTo($request, 'tour.index')
            ->with('success', 'Tour gespeichert.');
    }

    /**
     * Löscht (soft) eine Tour.
     */
    public function destroy(Request $request, int $id)
    {
        $tour = Tour::findOrFail($id);
        $tour->delete();

        return $this->redirectToReturnTo($request, 'tour.index')
            ->with('success', 'Tour gelöscht.');
    }

    /**
     * Reihenfolge der Touren in der Liste (Bulk, AJAX).
     * Request: items: [{id: number, reihenfolge: number}, ...]
     * Hinweis: Dein Model nutzt $table='tour' (singular) → Validation muss 'exists:tour,id' sein.
     */
    public function reorder(Request $request)
    {
        $items = $request->validate([
            'items'               => ['required', 'array', 'min:1'],
            'items.*.id'          => ['required', 'integer', 'exists:tour,id'],
            'items.*.reihenfolge' => ['required', 'integer', 'min:1'],
        ])['items'];

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                Tour::where('id', $item['id'])
                    ->update(['reihenfolge' => (int)$item['reihenfolge']]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Aktiv-Status umschalten (AJAX) – per Route Model Binding.
     * Request: aktiv: 0|1
     */
    public function toggleActive(Request $request, Tour $tour)
    {
        $data = $request->validate([
            'aktiv' => ['required', 'in:0,1'],
        ]);

        $tour->aktiv = (int)$data['aktiv'];
        $tour->save();

        return response()->json(['ok' => true, 'aktiv' => $tour->aktiv]);
    }

    /**
     * (Optional) Reihenfolge der Gebäude INNERHALB EINER Tour (AJAX).
     * Route: PATCH /tour/{tour}/gebaeude/reorder
     * Request: items: [{id: gebaeude_id, reihenfolge: number}, ...]
     */
    public function reorderGebaeude(Request $request, Tour $tour)
    {
        $items = $request->validate([
            'items'               => ['required', 'array', 'min:1'],
            'items.*.id'          => ['required', 'integer', 'exists:gebaeude,id'],
            'items.*.reihenfolge' => ['required', 'integer', 'min:1'],
        ])['items'];

        DB::transaction(function () use ($tour, $items) {
            foreach ($items as $i) {
                $tour->gebaeude()->updateExistingPivot((int)$i['id'], [
                    'reihenfolge' => (int)$i['reihenfolge']
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Hilfsfunktion: normalisiert die Gebäudeliste aus dem Request zu einem sync()-Array:
     *   [ gebaeude_id => ['reihenfolge' => X], ... ]
     *
     * Unterstützt:
     *   - gebaeude: [ {id: 12, reihenfolge: 1}, ... ]
     *   - gebaeude_ids: [12,34,...]  → Reihenfolge wird automatisch 1..N vergeben
     */
    private function buildGebaeudeSyncArray(Request $request): array
    {
        $sync = [];

        // Variante A: detaillierte Objekte mit id + reihenfolge
        if (is_array($request->input('gebaeude'))) {
            foreach ($request->input('gebaeude') as $row) {
                if (!isset($row['id'])) {
                    continue;
                }
                $gid = (int)$row['id'];
                $pos = isset($row['reihenfolge']) ? (int)$row['reihenfolge'] : null;
                if ($gid > 0) {
                    $sync[$gid] = ['reihenfolge' => max(1, (int)$pos)];
                }
            }
        }
        // Variante B: nur IDs – Reihung 1..N
        elseif (is_array($request->input('gebaeude_ids'))) {
            $pos = 1;
            foreach ($request->input('gebaeude_ids') as $gid) {
                $gid = (int)$gid;
                if ($gid > 0) {
                    $sync[$gid] = ['reihenfolge' => $pos++];
                }
            }
        }

        // Duplikate verhindern (zuletzt gewonnene zählt)
        // (array_unique greift hier nicht, da Werte Arrays sind – unsere Zuweisung überschreibt ohnehin.)
        return $sync;
    }

    // app/Http/Controllers/TourController.php

    public function bulkDetach(\Illuminate\Http\Request $request, int $tour)
    {
        // 1) Tour laden
        $tourModel = \App\Models\Tour::findOrFail($tour);

        // 2) Auswahl validieren: Array mit IDs
        $data = $request->validate([
            'gebaeude_ids'   => ['required', 'array', 'min:1'],
            'gebaeude_ids.*' => ['integer', 'exists:gebaeude,id'],
            'returnTo'       => ['nullable', 'url'],
        ]);

        // 3) Detach in einem Rutsch
        $tourModel->gebaeude()->detach($data['gebaeude_ids']);

        // 4) Zurück
        $back = $data['returnTo'] ?? route('tour.show', $tourModel->id);
        return redirect()->to($back)->with('success', 'Verknüpfungen entfernt.');
    }
}
