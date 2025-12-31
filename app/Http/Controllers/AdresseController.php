<?php

namespace App\Http\Controllers;

use App\Models\Adresse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class AdresseController extends Controller
{
    // Controller-Ausschnitt — nur diese Methode ersetzen
    private function safeRedirectAfterSave(Request $request, string $defaultRoute, string $message)
    {
        $returnTo = $request->input('returnTo');

        Log::debug('🔁 safeRedirectAfterSave called', [
            'method'   => $request->method(),
            'returnTo' => $returnTo,
            'url_base' => url('/'),
        ]);

        if ($returnTo) {
            $isAbsoluteInternal = str_starts_with($returnTo, url('/')); // z.B. http://localhost:8000/...
            $isRelative         = str_starts_with($returnTo, '/');      // z.B. /gebaeude/17/edit

            if ($isAbsoluteInternal || $isRelative) {
                // Pfad extrahieren (für absolute URL) oder direkt nehmen (für relative)
                $relativePath = $isAbsoluteInternal
                    ? (parse_url($returnTo, PHP_URL_PATH) ?? '/')
                    : $returnTo;

                // Doppelte/fehlende Slashes säubern
                $relativePath = '/' . ltrim($relativePath, '/');

                $safeUrl = url($relativePath);

                Log::debug('🔗 Redirect target resolved', [
                    'relativePath' => $relativePath,
                    'safeUrl'      => $safeUrl,
                ]);

                // Variante A: absolut (away) – stabil bei PUT/PATCH/POST
                return redirect()->away($safeUrl)->with('success', $message);
            }
        }

        Log::debug('ℹ️ Kein gültiges returnTo, Redirect auf defaultRoute', [
            'defaultRoute' => $defaultRoute,
        ]);

        return redirect()->route($defaultRoute)->with('success', $message);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $name = trim($request->get('name', ''));

        $adressen = \App\Models\Adresse::query()
            ->when(
                $name !== '',
                fn($q) =>
                $q->where('name', 'like', "%{$name}%")
            )
            ->orderBy('name')
            ->paginate(15)
            ->appends($request->query()); // damit der Name in der Pagination bleibt

        return view('adresse.index', compact('adressen', 'name'));
    }

    public function show($id)
    {
        $adresse = Adresse::findOrFail($id); // 404 automatisch bei Nichtfund
        return view('adresse.show', compact('adresse'));
    }

    public function edit(Request $request, $id)
    {
        $adresse = Adresse::findOrFail($id);

        $codiciUnivoci = \App\Models\Adresse::query()
            ->whereNotNull('codice_univoco')          // nur nicht-null
            ->where('codice_univoco', '!=', '')       // und nicht leer
            ->distinct()
            ->orderBy('codice_univoco')
            ->limit(200)                               // Performance-Schutz; bei Bedarf erhöhen
            ->pluck('codice_univoco');

        $strassen = \App\Models\Adresse::whereNotNull('strasse')
            ->where('strasse', '!=', '')
            ->distinct()->orderBy('strasse')->limit(300)->pluck('strasse');

        $returnTo = $request->query('returnTo'); // 🔹 optionaler Rücksprung-Link
        return view('adresse.edit', compact('adresse', 'returnTo', 'codiciUnivoci', 'strassen'));
    }

    public function update(Request $request, $id)
    {
        $adresse = Adresse::findOrFail($id);

        // ✅ Validierung gemäß deiner Tabelle
        $validated = $request->validate([
            'name'            => 'required|string|max:200',
            'strasse'         => 'nullable|string|max:255',
            'hausnummer'      => 'required|string|max:10',   // in DB: NOT NULL
            'plz'             => 'nullable|string|max:10',
            'wohnort'         => 'nullable|string|max:100',
            'provinz'         => 'required|string|max:4',    // in DB: NOT NULL
            'telefon'         => 'nullable|string|max:50',
            'handy'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'email_zweit'     => 'nullable|email|max:255',
            'pec'             => 'nullable|email|max:255',
            'steuernummer'    => 'nullable|string|max:50',
            'mwst_nummer'     => 'nullable|string|max:50',
            'codice_univoco'  => 'nullable|string|max:20',
            'land'            => 'nullable|string|max:50',
            'bemerkung'       => 'nullable|string',
        ]);

        // Optional kleine Normalisierung (ohne Zwang)
        if (isset($validated['provinz'])) {
            $validated['provinz'] = strtoupper(trim($validated['provinz']));
        }
        if (isset($validated['plz'])) {
            $validated['plz'] = trim($validated['plz']);
        }
        if (isset($validated['steuernummer'])) {
            $validated['steuernummer'] = strtoupper(trim($validated['steuernummer']));
        }

        $adresse->update($validated);

        return $this->safeRedirectAfterSave($request, 'adresse.index', '1Adresse erfolgreich angelegt.');
    }

    public function create(Request $request)
    {
        $returnTo = $request->query('returnTo'); // kann null sein
        $adresse = new \App\Models\Adresse();
        $codiciUnivoci = \App\Models\Adresse::query()
            ->whereNotNull('codice_univoco')          // nur nicht-null
            ->where('codice_univoco', '!=', '')       // und nicht leer
            ->distinct()
            ->orderBy('codice_univoco')
            ->limit(200)                               // Performance-Schutz; bei Bedarf erhöhen
            ->pluck('codice_univoco');

        $strassen = \App\Models\Adresse::whereNotNull('strasse')
            ->where('strasse', '!=', '')
            ->distinct()->orderBy('strasse')->limit(300)->pluck('strasse');


        return view('adresse.create', compact('adresse', 'returnTo', 'codiciUnivoci', 'strassen'));
    }

    public function store(Request $request)
    {
        // Validierung passend zu deinem Schema
        $validated = $request->validate([
            'name'            => 'required|string|max:200',
            'strasse'         => 'nullable|string|max:255',
            'hausnummer'      => 'required|string|max:10',   // DB: NOT NULL
            'plz'             => 'nullable|string|max:10',
            'wohnort'         => 'nullable|string|max:100',
            'provinz'         => 'required|string|max:4',    // DB: NOT NULL
            'telefon'         => 'nullable|string|max:50',
            'handy'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'email_zweit'     => 'nullable|email|max:255',
            'pec'             => 'nullable|email|max:255',
            'steuernummer'    => 'nullable|string|max:50',
            'mwst_nummer'     => 'nullable|string|max:50',
            'codice_univoco'  => 'nullable|string|max:20',
            'land'            => 'nullable|string|max:50',
            'bemerkung'       => 'nullable|string',
        ]);

        // optionale Normalisierung
        if (isset($validated['provinz'])) {
            $validated['provinz'] = strtoupper(trim($validated['provinz']));
        }
        if (isset($validated['steuernummer'])) {
            $validated['steuernummer'] = strtoupper(trim($validated['steuernummer']));
        }

        $adresse = Adresse::create($validated);

        return $this->safeRedirectAfterSave($request, 'adresse.index', '1Adresse erfolgreich angelegt.');
    }

    public function destroy($id)
    {
        $adresse = Adresse::findOrFail($id);

        try {
            $adresse->delete();
            return redirect()
                ->route('adresse.index')
                ->with('success', 'Adresse wurde gelöscht.');
        } catch (QueryException $e) {
            // z. B. wegen Fremdschlüssel-Verknüpfungen
            return back()->with('error', 'Adresse kann nicht gelöscht werden (verknüpfte Daten vorhanden).');
        }
    }

    public function bulkDestroy(Request $request)
    {
        // 1) Validierung: ids[] muss existieren und gültig sein
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:adressen,id'],
        ], [
            'ids.required' => 'Bitte wähle mindestens einen Eintrag aus.',
            'ids.min'      => 'Bitte wähle mindestens einen Eintrag aus.',
        ]);

        $ids = $data['ids'];

        // 2) Löschen – robust mit try/catch
        try {
            // Optional: Anzahl vorher ermitteln (zur Bestätigung)
            $count = \App\Models\Adresse::whereIn('id', $ids)->count();

            \App\Models\Adresse::whereIn('id', $ids)->delete();

            return redirect()
                ->route('adresse.index', $request->only('name')) // Filter (name) beibehalten
                ->with('success', $count . ' Adressen gelöscht.');
        } catch (QueryException $e) {
            // z. B. FK-Constraints
            return back()
                ->withInput()
                ->with('error', 'Einige Adressen konnten nicht gelöscht werden (verknüpfte Daten).');
        }
    }
}
