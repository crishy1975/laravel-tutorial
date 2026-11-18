<?php
// ═══════════════════════════════════════════════════════════════
// ZIEL: app/Http/Controllers/GemeindeMeldungController.php  (komplett ersetzen)
// ═══════════════════════════════════════════════════════════════
// Meldung nicht erreichter Kunden an die Gemeinde (Bürgermeister)

namespace App\Http\Controllers;

use App\Models\Gebaeude;
use App\Models\Gemeinde;
use App\Models\GemeindeMeldung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class GemeindeMeldungController extends Controller
{
    /**
     * Übersicht: alle "nicht erreicht", noch nicht gemeldet – gruppiert nach Gemeinde
     */
    public function index()
    {
        $gemeinden = Gemeinde::where('aktiv', true)->get();

        $gebaeude = Gebaeude::query()
            ->whereNotNull('nicht_erreicht_am')
            ->whereNull('gemeldet_am')
            ->sortByAddress()
            ->get();

        // Gruppieren: Gemeinde-ID oder 0 = keine Gemeinde gefunden
        $gruppen = $gebaeude
            ->groupBy(fn ($g) => Gemeinde::fuerOrt($g->wohnort, $gemeinden)?->id ?? 0)
            ->map(function ($liste, $gemeindeId) use ($gemeinden) {
                $gemeinde = $gemeindeId ? $gemeinden->firstWhere('id', $gemeindeId) : null;

                return [
                    'gemeinde'   => $gemeinde,
                    'gebaeude'   => $liste,
                    'empfaenger' => $gemeinde?->empfaenger,
                    'betreff'    => $gemeinde?->betreffVorlage() ?? Gemeinde::STANDARD_BETREFF,
                    'text'       => $gemeinde?->textFuer($liste) ?? '',
                ];
            })
            ->sortBy(fn ($gr) => $gr['gemeinde']?->name ?? 'zzz')
            ->values();

        $letzteMeldungen = GemeindeMeldung::with(['gemeinde', 'user'])
            ->latest('gesendet_am')
            ->limit(20)
            ->get();

        return view('reinigungsplanung.meldungen', compact('gruppen', 'letzteMeldungen'));
    }

    /**
     * Meldung senden (oder nur als gemeldet markieren)
     */
    public function senden(Request $request)
    {
        $data = $request->validate([
            'gemeinde_id'    => ['nullable', 'integer', 'exists:gemeinden,id'],
            'gebaeude_ids'   => ['required', 'array', 'min:1'],
            'gebaeude_ids.*' => ['integer', 'exists:gebaeude,id'],
            'empfaenger'     => ['nullable', 'email', 'max:255'],
            'betreff'        => ['required', 'string', 'max:255'],
            'text'           => ['required', 'string'],
            'aktion'         => ['required', 'in:senden,manuell'],
        ], [
            'gebaeude_ids.required' => 'Bitte mindestens ein Gebäude auswählen.',
            'empfaenger.email'      => 'Bitte eine gültige Empfängeradresse eingeben.',
        ]);

        $senden = $data['aktion'] === 'senden';

        if ($senden && empty($data['empfaenger'])) {
            return back()->withInput()->with('error', 'Kein Empfänger angegeben – bitte PEC/E-Mail eintragen.');
        }

        // 1) Mail versenden
        //    Empfänger ist PEC  → über Aruba PEC (Absender resch@pec.it)
        //    Empfänger ist keine PEC → über normale Firmen-Mail (Absender aus MAIL_FROM_ADDRESS)
        $gemeinde = !empty($data['gemeinde_id']) ? Gemeinde::find($data['gemeinde_id']) : null;
        $istPec   = $senden && $this->istPecAdresse($data['empfaenger'] ?? null, $gemeinde);

        if ($senden) {
            try {
                $mailer = $istPec ? $this->pecMailer() : Mail::mailer();

                $mailer->raw($data['text'], function ($m) use ($data, $istPec) {
                    if ($istPec) {
                        // Aruba PEC akzeptiert nur die eigene Adresse als Absender
                        $absender = config('mail.pec_from.address', 'resch@pec.it');
                        $name     = config('mail.pec_from.name', 'Resch GmbH');
                    } else {
                        $absender = config('mail.from.address');
                        $name     = config('mail.from.name');
                    }

                    $m->from($absender, $name)
                      ->sender($absender)
                      ->returnPath($absender)
                      ->to($data['empfaenger'])
                      ->subject($data['betreff']);

                    if ($kopie = config('mail.meldung_kopie')) {
                        $m->bcc($kopie);
                    }
                });
            } catch (Throwable $e) {
                Log::error('Gemeinde-Meldung Versand fehlgeschlagen', [
                    'empfaenger' => $data['empfaenger'],
                    'error'      => $e->getMessage(),
                ]);

                return back()->withInput()->with('error', 'Versand fehlgeschlagen: ' . $e->getMessage());
            }
        }

        // 2) Protokoll + Status setzen
        DB::transaction(function () use ($data, $senden, $istPec, $request) {
            GemeindeMeldung::create([
                'gemeinde_id'  => $data['gemeinde_id'] ?? null,
                'empfaenger'   => $data['empfaenger'] ?? null,
                'betreff'      => $data['betreff'],
                'text'         => $data['text'],
                'gebaeude_ids' => array_map('intval', $data['gebaeude_ids']),
                'versandart'   => $senden ? ($istPec ? 'pec' : 'mail') : 'manuell',
                'user_id'      => $request->user()?->id,
                'gesendet_am'  => now(),
            ]);

            Gebaeude::whereIn('id', $data['gebaeude_ids'])->update(['gemeldet_am' => now()]);
        });

        $anzahl = count($data['gebaeude_ids']);

        return redirect()
            ->route('reinigungsplanung.meldungen')
            ->with('success', $senden
                ? "Meldung an {$data['empfaenger']} gesendet – " . ($istPec ? 'per PEC' : 'per E-Mail') . " ({$anzahl} Gebäude)."
                : "{$anzahl} Gebäude als gemeldet markiert (ohne Versand).");
    }

    /**
     * Gespeicherte Meldung ansehen (Nachweis)
     */
    public function show(GemeindeMeldung $meldung)
    {
        return response($meldung->text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * Ist die Empfängeradresse eine PEC-Adresse?
     *
     * - entspricht der hinterlegten PEC der Gemeinde, oder
     * - Domain typisch für PEC (pec., legalmail., postacert., cert., …)
     */
    protected function istPecAdresse(?string $adresse, ?Gemeinde $gemeinde = null): bool
    {
        $adresse = mb_strtolower(trim((string) $adresse));
        if ($adresse === '') {
            return false;
        }

        if ($gemeinde && $gemeinde->pec && mb_strtolower(trim($gemeinde->pec)) === $adresse) {
            return true;
        }

        $domain = substr(strrchr($adresse, '@') ?: '', 1);

        return (bool) preg_match('/(^|\.)(pec|legalmail|postacert|cert|cgn|pecsicura|registerpec)\./', $domain);
    }

    /**
     * Mailer für PEC-Versand: "pec" falls konfiguriert, sonst Standard-Mailer
     */
    protected function pecMailer()
    {
        $name = config('mail.meldung_mailer', 'pec');

        return config("mail.mailers.$name") ? Mail::mailer($name) : Mail::mailer();
    }
}
