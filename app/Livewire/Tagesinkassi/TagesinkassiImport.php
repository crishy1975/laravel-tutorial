<?php
/*
════════════════════════════════════════════════════════════════════════════
DATEI: TagesinkassiImport.php
PFAD:  app/Livewire/Tagesinkassi/TagesinkassiImport.php
════════════════════════════════════════════════════════════════════════════
*/

namespace App\Livewire\Tagesinkassi;

use App\Models\Tagesinkasso;
use App\Models\TagesinkassiPosTransaktion;
use App\Services\PosTransaktionenImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('POS-Import – Tagesinkassi')]
class TagesinkassiImport extends Component
{
    use WithFileUploads;

    public $datei;
    public int $mwstSatz = 10;

    /** Vorschau pro Tag */
    public array $tage = [];
    public array $ignoriert = [];
    public int $zahlungenGesamt = 0;
    public int $bereitsImportiert = 0;
    public ?string $fehler = null;

    /**
     * Status pro Tag:
     *  neu        – kein Eintrag vorhanden → wird angelegt
     *  ergaenzen  – Eintrag stammt bisher nur aus POS → Betrag wird addiert
     *  gleich     – manueller Eintrag mit gleichem Betrag → nur verknüpfen, Betrag bleibt
     *  abweichend – manueller Eintrag mit anderem Betrag → standardmäßig nicht übernommen
     */
    public function updatedDatei(): void
    {
        $this->analysieren();
    }

    public function analysieren(): void
    {
        $this->reset(['tage', 'ignoriert', 'zahlungenGesamt', 'bereitsImportiert', 'fehler']);

        $this->validate(['datei' => 'required|file|max:10240'], [], ['datei' => 'Datei']);

        $endung = strtolower($this->datei->getClientOriginalExtension());
        if (!in_array($endung, ['xlsx', 'xls', 'csv'], true)) {
            $this->fehler = 'Bitte die Excel- oder CSV-Datei aus dem POS-Portal wählen (.xlsx, .xls, .csv).';
            return;
        }

        try {
            $ergebnis = app(PosTransaktionenImport::class)->lesen($this->datei->getRealPath(), $endung);
        } catch (\Throwable $e) {
            report($e);
            $this->fehler = 'Datei konnte nicht gelesen werden: ' . $e->getMessage();
            return;
        }

        $zahlungen = collect($ergebnis['zahlungen']);
        $this->ignoriert       = $ergebnis['ignoriert'];
        $this->zahlungenGesamt = $zahlungen->count();

        // Bereits importierte Zahlungen aussortieren
        $vorhanden = TagesinkassiPosTransaktion::whereIn('referenz', $zahlungen->pluck('referenz'))
            ->pluck('referenz')
            ->flip();

        $neu = $zahlungen->reject(fn($z) => isset($vorhanden[$z['referenz']]));
        $this->bereitsImportiert = $this->zahlungenGesamt - $neu->count();

        $proTag = $neu->groupBy(fn($z) => substr($z['datum_zeit'], 0, 10))->sortKeys();

        $bestehende = Tagesinkasso::whereIn('datum', $proTag->keys())
            ->get()
            ->keyBy(fn($e) => $e->datum->toDateString());

        $posBisher = TagesinkassiPosTransaktion::whereIn('tagesinkasso_id', $bestehende->pluck('id'))
            ->selectRaw('tagesinkasso_id, SUM(betrag) AS summe')
            ->groupBy('tagesinkasso_id')
            ->pluck('summe', 'tagesinkasso_id');

        foreach ($proTag as $datum => $liste) {
            $summe = round($liste->sum('betrag'), 2);
            $e     = $bestehende[$datum] ?? null;

            if (!$e) {
                $status = 'neu';
            } elseif (isset($posBisher[$e->id]) && abs($e->gesamt - (float) $posBisher[$e->id]) < 0.005) {
                $status = 'ergaenzen';
            } elseif (abs($e->gesamt - $summe) < 0.005) {
                $status = 'gleich';
            } else {
                $status = 'abweichend';
            }

            $this->tage[] = [
                'datum'         => $datum,
                'summe'         => $summe,
                'anzahl'        => $liste->count(),
                'bestehend'     => $e ? $e->gesamt : null,
                'status'        => $status,
                'auswahl'       => $status !== 'abweichend',
                'transaktionen' => $liste->sortBy('datum_zeit')->values()->all(),
            ];
        }
    }

    public function alleAuswaehlen(bool $wert): void
    {
        foreach ($this->tage as $i => $t) {
            $this->tage[$i]['auswahl'] = $wert;
        }
    }

    public function importieren()
    {
        $feld = $this->mwstSatz === 22 ? 'betrag_22' : 'betrag_10';

        $tageAnzahl = 0;
        $summeGesamt = 0.0;
        $letztesDatum = null;

        DB::transaction(function () use ($feld, &$tageAnzahl, &$summeGesamt, &$letztesDatum) {
            foreach ($this->tage as $t) {
                if (empty($t['auswahl'])) {
                    continue;
                }

                // Sicherheit: nochmals auf Duplikate prüfen
                $refs  = array_column($t['transaktionen'], 'referenz');
                $schon = TagesinkassiPosTransaktion::whereIn('referenz', $refs)->pluck('referenz')->flip();
                $trans = collect($t['transaktionen'])->reject(fn($z) => isset($schon[$z['referenz']]));

                if ($trans->isEmpty()) {
                    continue;
                }

                $summe = round($trans->sum('betrag'), 2);
                $e = Tagesinkasso::where('datum', $t['datum'])->lockForUpdate()->first();

                if (!$e) {
                    $e = Tagesinkasso::create([
                        'datum'     => $t['datum'],
                        $feld       => $summe,
                        'bemerkung' => 'POS' . ($trans->count() > 1 ? " ({$trans->count()} Zahlungen)" : ''),
                    ]);
                } elseif ($t['status'] !== 'gleich') {
                    $e->update([$feld => round((float) $e->{$feld} + $summe, 2)]);
                }

                foreach ($trans as $z) {
                    TagesinkassiPosTransaktion::create($z + ['tagesinkasso_id' => $e->id]);
                }

                $tageAnzahl++;
                $summeGesamt += $t['status'] === 'gleich' ? 0 : $summe;
                $letztesDatum = $t['datum'];
            }
        });

        if ($tageAnzahl === 0) {
            $this->fehler = 'Es wurde nichts importiert – keine Tage ausgewählt.';
            return null;
        }

        session()->flash('success', "POS-Import: {$tageAnzahl} Tage übernommen, "
            . number_format($summeGesamt, 2, ',', '.') . ' € neu verbucht.');

        $d = Carbon::parse($letztesDatum);

        return $this->redirectRoute('tagesinkassi.index', ['jahr' => $d->year, 'monat' => $d->month]);
    }

    public function render()
    {
        $auswahl = collect($this->tage)->where('auswahl', true);

        return view('livewire.tagesinkassi.tagesinkassi-import', [
            'auswahlTage'  => $auswahl->count(),
            'auswahlSumme' => round($auswahl->where('status', '!=', 'gleich')->sum('summe'), 2),
        ]);
    }
}
