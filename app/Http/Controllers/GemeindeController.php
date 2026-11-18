<?php
// app/Http/Controllers/GemeindeController.php
// Verwaltung der Gemeinden (Empfänger + Textvorlage für Meldungen)

namespace App\Http\Controllers;

use App\Models\Gebaeude;
use App\Models\Gemeinde;
use Illuminate\Http\Request;

class GemeindeController extends Controller
{
    public function index()
    {
        $gemeinden = Gemeinde::orderBy('name')->get();

        // Orte aus den Gebäuden, die noch keiner Gemeinde zugeordnet sind
        $alleOrte = Gebaeude::query()
            ->whereNotNull('wohnort')
            ->where('wohnort', '!=', '')
            ->selectRaw('wohnort, COUNT(*) as anzahl')
            ->groupBy('wohnort')
            ->orderByDesc('anzahl')
            ->get();

        $offeneOrte = $alleOrte->filter(fn ($o) => !Gemeinde::fuerOrt($o->wohnort, $gemeinden))->values();

        return view('gemeinden.index', [
            'gemeinden'       => $gemeinden,
            'offeneOrte'      => $offeneOrte,
            'standardBetreff' => Gemeinde::STANDARD_BETREFF,
            'standardText'    => Gemeinde::STANDARD_TEXT,
        ]);
    }

    public function store(Request $request)
    {
        Gemeinde::create($this->validiere($request));

        return redirect()->route('gemeinden.index')->with('success', 'Gemeinde angelegt.');
    }

    public function update(Request $request, Gemeinde $gemeinde)
    {
        $gemeinde->update($this->validiere($request));

        return redirect()->route('gemeinden.index')->with('success', 'Gemeinde gespeichert.');
    }

    public function destroy(Gemeinde $gemeinde)
    {
        $gemeinde->delete();

        return redirect()->route('gemeinden.index')->with('success', 'Gemeinde gelöscht.');
    }

    protected function validiere(Request $request): array
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'aliase'  => ['nullable', 'string', 'max:255'],
            'pec'     => ['nullable', 'email', 'max:255'],
            'email'   => ['nullable', 'email', 'max:255'],
            'betreff' => ['nullable', 'string', 'max:255'],
            'text'    => ['nullable', 'string'],
            'aktiv'   => ['nullable', 'boolean'],
        ], [
            'pec.email'   => 'Bitte eine gültige PEC-Adresse eingeben.',
            'email.email' => 'Bitte eine gültige E-Mail-Adresse eingeben.',
        ]);

        $data['aktiv'] = $request->boolean('aktiv', true);

        // Leere Vorlage = Standard verwenden
        foreach (['betreff', 'text'] as $f) {
            if (isset($data[$f]) && trim($data[$f]) === '') {
                $data[$f] = null;
            }
        }

        return $data;
    }
}
