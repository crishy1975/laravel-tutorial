<?php
/*
════════════════════════════════════════════════════════════════════════════
DATEI: TagesinkassiListe.php
PFAD:  app/Livewire/Tagesinkassi/TagesinkassiListe.php
════════════════════════════════════════════════════════════════════════════
*/

namespace App\Livewire\Tagesinkassi;

use App\Models\Tagesinkasso;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tagesinkassi')]
class TagesinkassiListe extends Component
{
    #[Url]
    public int $jahr;

    #[Url]
    public int $monat;

    // ── Neuer Eintrag ──────────────────────────────────────────────
    public string $neuDatum = '';
    public string $neuBetrag10 = '';
    public string $neuBetrag22 = '';
    public string $neuBemerkung = '';

    // ── Bearbeiten ─────────────────────────────────────────────────
    public ?int $editId = null;
    public string $editDatum = '';
    public string $editBetrag10 = '';
    public string $editBetrag22 = '';
    public string $editBemerkung = '';

    public function mount(): void
    {
        $this->jahr  = $this->jahr  ?? (int) now()->year;
        $this->monat = $this->monat ?? (int) now()->month;
        $this->setzeNeuDatum();
    }

    // ════════════════════════════════════════════════════════════════
    // Monatsnavigation
    // ════════════════════════════════════════════════════════════════

    public function monatWechseln(int $delta): void
    {
        $d = Carbon::create($this->jahr, $this->monat, 1)->addMonths($delta);
        $this->jahr  = $d->year;
        $this->monat = $d->month;
        $this->nachMonatswechsel();
    }

    public function aktuellerMonat(): void
    {
        $this->jahr  = (int) now()->year;
        $this->monat = (int) now()->month;
        $this->nachMonatswechsel();
    }

    public function updatedJahr(): void  { $this->nachMonatswechsel(); }
    public function updatedMonat(): void { $this->nachMonatswechsel(); }

    private function nachMonatswechsel(): void
    {
        $this->abbrechen();
        $this->resetErrorBag();
        $this->setzeNeuDatum();
    }

    /** Heute, falls im gewählten Monat – sonst der 1. des Monats */
    private function setzeNeuDatum(): void
    {
        $heute = now();
        $this->neuDatum = ($heute->year === $this->jahr && $heute->month === $this->monat)
            ? $heute->toDateString()
            : Carbon::create($this->jahr, $this->monat, 1)->toDateString();
    }

    // ════════════════════════════════════════════════════════════════
    // Neu anlegen
    // ════════════════════════════════════════════════════════════════

    public function speichern(): void
    {
        $this->resetErrorBag();

        $datum = $this->pruefeDatum($this->neuDatum, 'neuDatum');
        $b10   = $this->pruefeBetrag($this->neuBetrag10, 'neuBetrag10');
        $b22   = $this->pruefeBetrag($this->neuBetrag22, 'neuBetrag22');

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        if ($b10 + $b22 <= 0) {
            $this->addError('neuBetrag10', 'Bitte einen Betrag eingeben.');
            return;
        }

        if (Tagesinkasso::where('datum', $datum->toDateString())->exists()) {
            $this->addError('neuDatum', 'Für ' . $datum->format('d.m.Y') . ' gibt es schon einen Eintrag – bitte dort bearbeiten.');
            return;
        }

        Tagesinkasso::create([
            'datum'     => $datum->toDateString(),
            'betrag_10' => $b10,
            'betrag_22' => $b22,
            'bemerkung' => trim($this->neuBemerkung) ?: null,
        ]);

        // Monat mitziehen, falls in anderem Monat erfasst
        $this->jahr  = $datum->year;
        $this->monat = $datum->month;

        $this->neuBetrag10  = '';
        $this->neuBetrag22  = '';
        $this->neuBemerkung = '';

        session()->flash('success', $this->wochentagDatum($datum) . ': ' . $this->eur($b10 + $b22) . ' gespeichert.');
        $this->dispatch('fokus-betrag');
    }

    // ════════════════════════════════════════════════════════════════
    // Bearbeiten
    // ════════════════════════════════════════════════════════════════

    public function bearbeiten(int $id): void
    {
        $e = Tagesinkasso::findOrFail($id);

        $this->resetErrorBag();
        $this->editId        = $e->id;
        $this->editDatum     = $e->datum->toDateString();
        $this->editBetrag10  = $this->fuerEingabe($e->betrag_10);
        $this->editBetrag22  = $this->fuerEingabe($e->betrag_22);
        $this->editBemerkung = (string) $e->bemerkung;
    }

    public function aktualisieren(): void
    {
        if (!$this->editId) {
            return;
        }

        $this->resetErrorBag();

        $datum = $this->pruefeDatum($this->editDatum, 'editDatum');
        $b10   = $this->pruefeBetrag($this->editBetrag10, 'editBetrag10');
        $b22   = $this->pruefeBetrag($this->editBetrag22, 'editBetrag22');

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        if ($b10 + $b22 <= 0) {
            $this->addError('editBetrag10', 'Betrag fehlt – zum Entfernen bitte löschen.');
            return;
        }

        $doppelt = Tagesinkasso::where('datum', $datum->toDateString())
            ->where('id', '!=', $this->editId)
            ->exists();

        if ($doppelt) {
            $this->addError('editDatum', 'Für dieses Datum gibt es schon einen Eintrag.');
            return;
        }

        Tagesinkasso::whereKey($this->editId)->update([
            'datum'     => $datum->toDateString(),
            'betrag_10' => $b10,
            'betrag_22' => $b22,
            'bemerkung' => trim($this->editBemerkung) ?: null,
        ]);

        $this->abbrechen();
        session()->flash('success', 'Eintrag ' . $datum->format('d.m.Y') . ' aktualisiert.');
    }

    public function abbrechen(): void
    {
        $this->editId = null;
        $this->editDatum = $this->editBetrag10 = $this->editBetrag22 = $this->editBemerkung = '';
    }

    public function loeschen(int $id): void
    {
        $e = Tagesinkasso::find($id);
        if ($e) {
            $text = $e->datum->format('d.m.Y');
            $e->delete();
            session()->flash('success', "Eintrag {$text} gelöscht.");
        }
        $this->abbrechen();
    }

    // ════════════════════════════════════════════════════════════════
    // Hilfsfunktionen
    // ════════════════════════════════════════════════════════════════

    private function pruefeDatum(string $wert, string $feld): ?Carbon
    {
        try {
            $d = Carbon::createFromFormat('Y-m-d', $wert);
            if ($d === false || $d->format('Y-m-d') !== $wert) {
                throw new \Exception();
            }
            return $d->startOfDay();
        } catch (\Throwable $e) {
            $this->addError($feld, 'Ungültiges Datum.');
            return null;
        }
    }

    /** Akzeptiert 90 | 90,5 | 90.50 | 1.234,50 | "90 €" – leer = 0 */
    private function pruefeBetrag(string $wert, string $feld): float
    {
        $wert = trim(str_replace(['€', ' ', "\u{00A0}"], '', $wert));

        if ($wert === '') {
            return 0.0;
        }

        if (str_contains($wert, ',')) {
            $wert = str_replace('.', '', $wert);
            $wert = str_replace(',', '.', $wert);
        }

        if (!is_numeric($wert) || (float) $wert < 0) {
            $this->addError($feld, 'Ungültiger Betrag.');
            return 0.0;
        }

        return round((float) $wert, 2);
    }

    private function fuerEingabe($betrag): string
    {
        return (float) $betrag == 0 ? '' : number_format((float) $betrag, 2, ',', '');
    }

    private function eur(float $v): string
    {
        return number_format($v, 2, ',', '.') . ' €';
    }

    private function wochentagDatum(Carbon $d): string
    {
        return $d->copy()->locale('de')->isoFormat('dddd, D. MMMM YYYY');
    }

    // ════════════════════════════════════════════════════════════════
    // Render
    // ════════════════════════════════════════════════════════════════

    public function render()
    {
        $eintraege = Tagesinkasso::imMonat($this->jahr, $this->monat)
            ->orderBy('datum')
            ->get();

        $summe10 = round((float) $eintraege->sum('betrag_10'), 2);
        $summe22 = round((float) $eintraege->sum('betrag_22'), 2);

        $jahr = Tagesinkasso::imJahr($this->jahr)
            ->selectRaw('COALESCE(SUM(betrag_10),0) AS s10, COALESCE(SUM(betrag_22),0) AS s22, COUNT(*) AS anzahl')
            ->first();

        // Wochentag-Vorschau für das Eingabefeld
        $neuWochentag = '';
        try {
            if ($this->neuDatum) {
                $neuWochentag = Carbon::createFromFormat('Y-m-d', $this->neuDatum)->locale('de')->isoFormat('dddd');
            }
        } catch (\Throwable $e) {}

        $monatLabel = Carbon::create($this->jahr, $this->monat, 1)->locale('de')->isoFormat('MMMM YYYY');

        return view('livewire.tagesinkassi.tagesinkassi-liste', [
            'eintraege'    => $eintraege,
            'summe10'      => $summe10,
            'summe22'      => $summe22,
            'summeMonat'   => round($summe10 + $summe22, 2),
            'summeJahr'    => round((float) $jahr->s10 + (float) $jahr->s22, 2),
            'tageJahr'     => (int) $jahr->anzahl,
            'neuWochentag' => $neuWochentag,
            'monatLabel'   => $monatLabel,
        ]);
    }
}
