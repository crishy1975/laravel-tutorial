<?php

namespace App\Http\Controllers;

use App\Models\Gebaeude;
use App\Models\GebaeudeDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GebaeudeDocumentController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // 📋 INDEX - Alle Dokumente (mit Filter)
    // ═══════════════════════════════════════════════════════════

    /**
     * Übersicht aller Dokumente
     */
    public function index(Request $request)
    {
        $query = GebaeudeDocument::with(['gebaeude', 'uploader'])
            ->orderByDesc('created_at');

        // Filter: Gebäude
        if ($request->filled('gebaeude_id')) {
            $query->where('gebaeude_id', $request->gebaeude_id);
        }

        // Filter: Kategorie
        if ($request->filled('kategorie')) {
            $query->where('kategorie', $request->kategorie);
        }

        // Filter: Dateityp (Gruppe)
        if ($request->filled('typ')) {
            switch ($request->typ) {
                case 'bild':
                    $query->where('dateityp', 'like', 'image/%');
                    break;
                case 'pdf':
                    $query->where('dateiendung', 'pdf');
                    break;
                case 'office':
                    $query->whereIn('dateiendung', ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
                    break;
                case 'sonstige':
                    $query->where('dateityp', 'not like', 'image/%')
                          ->where('dateiendung', '!=', 'pdf')
                          ->whereNotIn('dateiendung', ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
                    break;
            }
        }

        // Filter: Wichtig
        if ($request->filled('wichtig')) {
            $query->where('ist_wichtig', true);
        }

        // Filter: Archiviert
        if ($request->boolean('archiviert', false)) {
            $query->where('ist_archiviert', true);
        } else {
            $query->where('ist_archiviert', false);
        }

        // Filter: Suche
        if ($request->filled('suche')) {
            $query->search($request->suche);
        }

        // Filter: Datum von/bis
        if ($request->filled('von')) {
            $query->whereDate('created_at', '>=', $request->von);
        }
        if ($request->filled('bis')) {
            $query->whereDate('created_at', '<=', $request->bis);
        }

        $dokumente = $query->paginate(25)->withQueryString();

        // Für Filter-Dropdowns
        $gebaeudeListe = Gebaeude::orderBy('codex')
            ->get(['id', 'codex', 'gebaeude_name', 'strasse']);
        
        $kategorien = GebaeudeDocument::KATEGORIEN;

        // Statistiken
        $stats = [
            'gesamt' => GebaeudeDocument::aktiv()->count(),
            'bilder' => GebaeudeDocument::aktiv()->bilder()->count(),
            'pdfs' => GebaeudeDocument::aktiv()->pdfs()->count(),
            'wichtig' => GebaeudeDocument::aktiv()->wichtig()->count(),
            'speicher' => GebaeudeDocument::aktiv()->sum('dateigroesse'),
        ];

        return view('gebaeude-dokumente.index', compact(
            'dokumente',
            'gebaeudeListe',
            'kategorien',
            'stats'
        ));
    }

    // ═══════════════════════════════════════════════════════════
    // ⬆️ UPLOAD
    // ═══════════════════════════════════════════════════════════

    /**
     * Dokument hochladen
     */
    public function store(Request $request, int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);

        $validated = $request->validate([
            'datei' => [
                'required',
                'file',
                'max:' . (GebaeudeDocument::maxDateigroesse() / 1024), // KB
                'mimes:' . implode(',', GebaeudeDocument::erlaubteEndungen()),
            ],
            'titel' => ['nullable', 'string', 'max:255'],
            'beschreibung' => ['nullable', 'string', 'max:2000'],
            'kategorie' => ['nullable', 'string', 'in:' . implode(',', array_keys(GebaeudeDocument::KATEGORIEN))],
            'dokument_datum' => ['nullable', 'date'],
            'tags' => ['nullable', 'string', 'max:500'],
            'ist_wichtig' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('datei');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Dateiname generieren
        $dateiname = GebaeudeDocument::generateDateiname($originalName);
        $pfad = GebaeudeDocument::generatePfad($gebaeude->id, $dateiname);

        // In Storage speichern
        $file->storeAs(
            "gebaeude-dokumente/{$gebaeude->id}",
            $dateiname,
            'local'
        );

        // Titel aus Dateiname, falls nicht angegeben
        $titel = $validated['titel'] ?? pathinfo($originalName, PATHINFO_FILENAME);

        // Kategorie automatisch erkennen
        $kategorie = $validated['kategorie'] ?? $this->erkenneKategorie($extension, $mimeType);

        // DB-Eintrag erstellen
        $dokument = GebaeudeDocument::create([
            'gebaeude_id' => $gebaeude->id,
            'titel' => $titel,
            'beschreibung' => $validated['beschreibung'] ?? null,
            'kategorie' => $kategorie,
            'dateiname' => $dateiname,
            'original_name' => $originalName,
            'dateityp' => $mimeType,
            'dateiendung' => $extension,
            'dateigroesse' => $size,
            'pfad' => $pfad,
            'dokument_datum' => $validated['dokument_datum'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'ist_wichtig' => $validated['ist_wichtig'] ?? false,
            'hochgeladen_von' => Auth::id(),
        ]);

        Log::info('Dokument hochgeladen', [
            'dokument_id' => $dokument->id,
            'gebaeude_id' => $gebaeude->id,
            'dateiname' => $originalName,
            'user_id' => Auth::id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Dokument erfolgreich hochgeladen.',
                'dokument' => [
                    'id' => $dokument->id,
                    'titel' => $dokument->titel,
                    'icon' => $dokument->icon,
                    'groesse' => $dokument->dateigroesse_formatiert,
                    'download_url' => $dokument->download_url,
                ],
            ]);
        }

        return back()->with('success', "Dokument \"{$dokument->titel}\" erfolgreich hochgeladen.");
    }

    /**
     * Mehrere Dokumente hochladen (AJAX)
     */
    public function storeMultiple(Request $request, int $gebaeudeId): JsonResponse
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);

        $request->validate([
            'dateien' => ['required', 'array', 'min:1', 'max:20'],
            'dateien.*' => [
                'file',
                'max:' . (GebaeudeDocument::maxDateigroesse() / 1024),
                'mimes:' . implode(',', GebaeudeDocument::erlaubteEndungen()),
            ],
            'kategorie' => ['nullable', 'string', 'in:' . implode(',', array_keys(GebaeudeDocument::KATEGORIEN))],
        ]);

        $kategorie = $request->kategorie;
        $uploaded = [];
        $errors = [];

        foreach ($request->file('dateien') as $file) {
            try {
                $originalName = $file->getClientOriginalName();
                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();
                $size = $file->getSize();

                $dateiname = GebaeudeDocument::generateDateiname($originalName);
                $pfad = GebaeudeDocument::generatePfad($gebaeude->id, $dateiname);

                $file->storeAs(
                    "gebaeude-dokumente/{$gebaeude->id}",
                    $dateiname,
                    'local'
                );

                $dokument = GebaeudeDocument::create([
                    'gebaeude_id' => $gebaeude->id,
                    'titel' => pathinfo($originalName, PATHINFO_FILENAME),
                    'kategorie' => $kategorie ?? $this->erkenneKategorie($extension, $mimeType),
                    'dateiname' => $dateiname,
                    'original_name' => $originalName,
                    'dateityp' => $mimeType,
                    'dateiendung' => $extension,
                    'dateigroesse' => $size,
                    'pfad' => $pfad,
                    'hochgeladen_von' => Auth::id(),
                ]);

                $uploaded[] = [
                    'id' => $dokument->id,
                    'titel' => $dokument->titel,
                    'icon' => $dokument->icon,
                ];
            } catch (\Exception $e) {
                $errors[] = $originalName ?? 'Unbekannte Datei';
                Log::error('Fehler beim Upload', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ok' => count($uploaded) > 0,
            'message' => count($uploaded) . ' Dokument(e) hochgeladen.',
            'uploaded' => $uploaded,
            'errors' => $errors,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // ✏️ UPDATE
    // ═══════════════════════════════════════════════════════════

    /**
     * Dokument-Metadaten aktualisieren
     */
    public function update(Request $request, int $id)
    {
        $dokument = GebaeudeDocument::findOrFail($id);

        $validated = $request->validate([
            'titel' => ['required', 'string', 'max:255'],
            'beschreibung' => ['nullable', 'string', 'max:2000'],
            'kategorie' => ['nullable', 'string', 'in:' . implode(',', array_keys(GebaeudeDocument::KATEGORIEN))],
            'dokument_datum' => ['nullable', 'date'],
            'tags' => ['nullable', 'string', 'max:500'],
            'ist_wichtig' => ['nullable', 'boolean'],
        ]);

        $validated['ist_wichtig'] = $validated['ist_wichtig'] ?? false;

        $dokument->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Dokument aktualisiert.',
            ]);
        }

        return back()->with('success', 'Dokument aktualisiert.');
    }

    // ═══════════════════════════════════════════════════════════
    // 🗑️ DELETE
    // ═══════════════════════════════════════════════════════════

    /**
     * Dokument löschen (inkl. Datei)
     */
    public function destroy(Request $request, int $id)
    {
        $dokument = GebaeudeDocument::findOrFail($id);
        $titel = $dokument->titel;
        $gebaeudeId = $dokument->gebaeude_id;

        // Datei und DB-Eintrag löschen
        $dokument->loeschenMitDatei();

        Log::info('Dokument gelöscht', [
            'dokument_id' => $id,
            'titel' => $titel,
            'gebaeude_id' => $gebaeudeId,
            'user_id' => Auth::id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Dokument gelöscht.',
            ]);
        }

        return back()->with('success', "Dokument \"{$titel}\" gelöscht.");
    }

    // ═══════════════════════════════════════════════════════════
    // ⬇️ DOWNLOAD & PREVIEW
    // ═══════════════════════════════════════════════════════════

    /**
     * Dokument herunterladen
     */
    public function download(int $id): StreamedResponse
    {
        $dokument = GebaeudeDocument::findOrFail($id);

        if (!$dokument->existiert()) {
            abort(404, 'Datei nicht gefunden.');
        }

        return Storage::disk('local')->download(
            $dokument->pfad,
            $dokument->original_name,
            ['Content-Type' => $dokument->dateityp]
        );
    }

    /**
     * Vorschau (Bilder, PDFs)
     */
    public function preview(int $id)
    {
        $dokument = GebaeudeDocument::findOrFail($id);

        if (!$dokument->existiert()) {
            abort(404, 'Datei nicht gefunden.');
        }

        // Nur Bilder und PDFs erlauben
        if (!$dokument->ist_bild && !$dokument->ist_pdf) {
            return redirect($dokument->download_url);
        }

        return Storage::disk('local')->response(
            $dokument->pfad,
            null,
            ['Content-Type' => $dokument->dateityp]
        );
    }

    /**
     * Thumbnail für Bilder (optional)
     */
    public function thumbnail(int $id)
    {
        $dokument = GebaeudeDocument::findOrFail($id);

        if (!$dokument->ist_bild || !$dokument->existiert()) {
            abort(404);
        }

        // Hier könnte man Intervention Image für echte Thumbnails nutzen
        // Fürs Erste: Original-Bild zurückgeben
        return Storage::disk('local')->response(
            $dokument->pfad,
            null,
            ['Content-Type' => $dokument->dateityp]
        );
    }

    // ═══════════════════════════════════════════════════════════
    // ⭐ QUICK ACTIONS
    // ═══════════════════════════════════════════════════════════

    /**
     * Als wichtig markieren/entfernen (AJAX)
     */
    public function toggleWichtig(int $id): JsonResponse
    {
        $dokument = GebaeudeDocument::findOrFail($id);
        $dokument->markiereWichtig(!$dokument->ist_wichtig);

        return response()->json([
            'ok' => true,
            'ist_wichtig' => $dokument->fresh()->ist_wichtig,
            'message' => $dokument->ist_wichtig ? 'Als wichtig markiert.' : 'Markierung entfernt.',
        ]);
    }

    /**
     * Archivieren/Wiederherstellen (AJAX)
     */
    public function toggleArchiv(int $id): JsonResponse
    {
        $dokument = GebaeudeDocument::findOrFail($id);
        $dokument->archivieren(!$dokument->ist_archiviert);

        return response()->json([
            'ok' => true,
            'ist_archiviert' => $dokument->fresh()->ist_archiviert,
            'message' => $dokument->ist_archiviert ? 'Archiviert.' : 'Wiederhergestellt.',
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 🔧 HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Kategorie automatisch erkennen
     */
    private function erkenneKategorie(string $extension, string $mimeType): string
    {
        // Bilder
        if (Str::startsWith($mimeType, 'image/')) {
            return 'foto';
        }

        // Office-Dokumente
        if (in_array($extension, ['doc', 'docx', 'odt', 'rtf'])) {
            return 'korrespondenz';
        }

        if (in_array($extension, ['xls', 'xlsx', 'ods', 'csv'])) {
            return 'sonstiges';
        }

        return 'sonstiges';
    }
}
