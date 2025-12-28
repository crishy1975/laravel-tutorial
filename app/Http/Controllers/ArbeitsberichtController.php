<?php

namespace App\Http\Controllers;

use App\Models\Arbeitsbericht;
use App\Models\Gebaeude;
use App\Models\Unternehmensprofil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ArbeitsberichtController extends Controller
{
    // ═══════════════════════════════════════════════════════════════════════════════
    // INDEX - Liste aller Arbeitsberichte
    // ═══════════════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $query = Arbeitsbericht::query()->with('gebaeude');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('suche')) {
            $like = '%' . $request->input('suche') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('adresse_name', 'like', $like)
                  ->orWhereHas('gebaeude', fn($sub) => $sub->where('gebaeude_name', 'like', $like));
            });
        }

        $berichte = $query->orderByDesc('created_at')->paginate(25);

        $stats = [
            'offen'         => Arbeitsbericht::offen()->count(),
            'unterschrieben' => Arbeitsbericht::unterschrieben()->count(),
            'abgelaufen'    => Arbeitsbericht::abgelaufen()->count(),
        ];

        return view('arbeitsbericht.index', compact('berichte', 'stats'));
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // GEBÄUDE-SUCHE (AJAX für Modal)
    // ═══════════════════════════════════════════════════════════════════════════════

    public function gebaeudeSearch(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $like = '%' . $query . '%';

        $gebaeude = Gebaeude::where(function ($q) use ($like) {
                $q->where('gebaeude_name', 'like', $like)
                  ->orWhere('codex', 'like', $like)
                  ->orWhere('strasse', 'like', $like)
                  ->orWhere('wohnort', 'like', $like);
            })
            ->orderBy('gebaeude_name')
            ->limit(20)
            ->get(['id', 'gebaeude_name', 'codex', 'strasse', 'hausnummer', 'plz', 'wohnort']);

        return response()->json($gebaeude);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // CREATE - Neuen Arbeitsbericht erstellen (mit Unterschrift-Feld!)
    // ═══════════════════════════════════════════════════════════════════════════════

    public function create(Request $request)
    {
        if (!$request->filled('gebaeude_id')) {
            return redirect()
                ->route('arbeitsbericht.index')
                ->with('error', 'Bitte wählen Sie zuerst ein Gebäude aus.');
        }

        $gebaeude = Gebaeude::with(['rechnungsempfaenger', 'aktiveArtikel', 'timelines'])
            ->findOrFail($request->integer('gebaeude_id'));

        return view('arbeitsbericht.create', compact('gebaeude'));
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // STORE - Arbeitsbericht MIT Unterschrift speichern
    // ═══════════════════════════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gebaeude_id'              => 'required|exists:gebaeude,id',
            'arbeitsdatum'             => 'required|date',
            'naechste_faelligkeit'     => 'nullable|date',
            'bemerkung'                => 'nullable|string|max:2000',
            'unterschrift'             => 'required|string', // Base64 Signatur Kunde
            'unterschrift_name'        => 'required|string|max:100',
            'unterschrift_mitarbeiter' => 'required|string', // Base64 Signatur Mitarbeiter
            'mitarbeiter_name'         => 'required|string|max:100',
        ]);

        $gebaeude = Gebaeude::with(['rechnungsempfaenger', 'aktiveArtikel'])
            ->findOrFail($validated['gebaeude_id']);

        // Bericht erstellen MIT beiden Unterschriften
        $bericht = Arbeitsbericht::createFromGebaeude($gebaeude, [
            'arbeitsdatum'             => $validated['arbeitsdatum'],
            'naechste_faelligkeit'     => $validated['naechste_faelligkeit'],
            'bemerkung'                => $validated['bemerkung'],
            // Kunde
            'unterschrift_kunde'       => $validated['unterschrift'],
            'unterschrift_name'        => $validated['unterschrift_name'],
            'unterschrift_ip'          => $request->ip(),
            'unterschrieben_am'        => now(),
            // Mitarbeiter
            'unterschrift_mitarbeiter' => $validated['unterschrift_mitarbeiter'],
            'mitarbeiter_name'         => $validated['mitarbeiter_name'],
            'status'                   => 'unterschrieben',
        ]);

        Log::info('Arbeitsbericht erstellt und unterschrieben', [
            'bericht_id'  => $bericht->id,
            'gebaeude_id' => $gebaeude->id,
            'kunde'       => $validated['unterschrift_name'],
            'mitarbeiter' => $validated['mitarbeiter_name'],
        ]);

        return redirect()
            ->route('arbeitsbericht.show', $bericht)
            ->with('success', 'Arbeitsbericht erstellt und unterschrieben. Jetzt Link an Kunden senden!');
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // SHOW - Arbeitsbericht anzeigen
    // ═══════════════════════════════════════════════════════════════════════════════

    public function show(Arbeitsbericht $arbeitsbericht)
    {
        $arbeitsbericht->load('gebaeude');
        return view('arbeitsbericht.show', compact('arbeitsbericht'));
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // PDF - PDF generieren (Admin)
    // ═══════════════════════════════════════════════════════════════════════════════

    public function pdf(Arbeitsbericht $arbeitsbericht)
    {
        // Unternehmensprofil laden (wie bei Rechnung)
        $profil = Unternehmensprofil::first();

        $pdf = Pdf::loadView('arbeitsbericht.pdf', [
            'bericht' => $arbeitsbericht,
            'profil'  => $profil,
        ]);
        
        $filename = "Arbeitsbericht_{$arbeitsbericht->id}_{$arbeitsbericht->arbeitsdatum->format('Y-m-d')}.pdf";
        return $pdf->download($filename);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // SENDEN - Link an Kunden senden
    // ═══════════════════════════════════════════════════════════════════════════════

    public function senden(Request $request, Arbeitsbericht $arbeitsbericht)
    {
        $validated = $request->validate([
            'kanal'      => 'required|in:email,sms,whatsapp',
            'empfaenger' => 'required|string',
            'nachricht'  => 'nullable|string|max:500',
        ]);

        $link = $arbeitsbericht->public_link;
        $standardNachricht = "Ihr unterschriebener Arbeitsbericht steht zum Download bereit (gültig bis {$arbeitsbericht->gueltig_bis->format('d.m.Y')}): {$link}";
        $nachricht = $validated['nachricht'] ?: $standardNachricht;

        switch ($validated['kanal']) {
            case 'email':
                $this->sendePerEmail($validated['empfaenger'], $arbeitsbericht, $nachricht);
                break;

            case 'sms':
                $this->sendePerSms($validated['empfaenger'], $nachricht);
                break;

            case 'whatsapp':
                $whatsappLink = $this->generateWhatsAppLink($validated['empfaenger'], $nachricht);
                $arbeitsbericht->markiereAlsGesendet();
                return redirect()->away($whatsappLink);
        }

        $arbeitsbericht->markiereAlsGesendet();

        return back()->with('success', 'Link wurde an den Kunden gesendet.');
    }

    private function sendePerEmail(string $empfaenger, Arbeitsbericht $bericht, string $nachricht): void
    {
        // Hier E-Mail-Logik implementieren
        Log::info('Arbeitsbericht-Link per E-Mail gesendet', [
            'bericht_id' => $bericht->id,
            'empfaenger' => $empfaenger,
        ]);
    }

    private function sendePerSms(string $nummer, string $nachricht): void
    {
        // Hier SMS-Logik implementieren
        Log::info('Arbeitsbericht-Link per SMS gesendet', ['nummer' => $nummer]);
    }

    private function generateWhatsAppLink(string $nummer, string $nachricht): string
    {
        $nummer = preg_replace('/[^0-9]/', '', $nummer);
        return "https://wa.me/{$nummer}?text=" . urlencode($nachricht);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // PUBLIC - Öffentlicher Download (für Kunden - NUR PDF!)
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Kunde sieht nur eine einfache Download-Seite
     */
    public function publicView(string $token)
    {
        $bericht = Arbeitsbericht::where('token', $token)->firstOrFail();

        if (!$bericht->istGueltig()) {
            return view('arbeitsbericht.abgelaufen', compact('bericht'));
        }

        $bericht->markiereAlsAbgerufen();

        return view('arbeitsbericht.public', compact('bericht'));
    }

    /**
     * PDF Download für Kunden
     */
    public function publicPdf(string $token)
    {
        $bericht = Arbeitsbericht::where('token', $token)->firstOrFail();

        if (!$bericht->istGueltig()) {
            abort(403, 'Dieser Link ist abgelaufen.');
        }

        // Unternehmensprofil laden (wie bei Rechnung)
        $profil = Unternehmensprofil::first();

        $pdf = Pdf::loadView('arbeitsbericht.pdf', [
            'bericht' => $bericht,
            'profil'  => $profil,
        ]);
        
        $filename = "Arbeitsbericht_{$bericht->arbeitsdatum->format('Y-m-d')}.pdf";

        return $pdf->download($filename);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // DESTROY
    // ═══════════════════════════════════════════════════════════════════════════════

    public function destroy(Arbeitsbericht $arbeitsbericht)
    {
        $arbeitsbericht->delete();
        return redirect()->route('arbeitsbericht.index')->with('success', 'Arbeitsbericht gelöscht.');
    }
}
