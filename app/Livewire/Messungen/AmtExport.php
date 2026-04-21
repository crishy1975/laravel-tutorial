<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use App\Models\Messung;
use App\Models\Impianto;
use App\Mail\MessungenExportMail;
use App\Services\AmtExportService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AmtExport extends Component
{
    public bool $show = false;

    // Filter
    public ?int $jahr = null;
    public bool $nurNichtExportiert = true;
    public ?string $vonDatum = null;
    public ?string $bisDatum = null;

    // Explizite Auswahl (überschreibt Filter, wenn gesetzt)
    public array $messungIds = [];

    // E-Mail
    public string $empfaenger = '';
    public string $zusatzNachricht = '';

    // State
    public ?string $errorMessage = null;
    public ?array $validationReport = null;   // ['valid_count' => N, 'invalid' => [...]]
    public ?string $previewText = null;       // Erste N Zeilen als Preview

    public function mount(): void
    {
        $this->jahr = (int) date('Y');
        $this->empfaenger = session('amt_export_empfaenger', config('messungen.amt_email_default', ''));
    }

    public function open(?int $jahr = null, ?array $messungIds = null): void
    {
        $this->jahr = $jahr ?? (int) date('Y');
        $this->messungIds = $messungIds ?? [];
        $this->errorMessage = null;
        $this->validationReport = null;
        $this->previewText = null;
        $this->show = true;
        $this->analyze();
    }

    public function close(): void
    {
        $this->show = false;
    }

    /**
     * Analysiert, welche Messungen exportiert werden, und validiert sie.
     */
    public function analyze(): void
    {
        $this->errorMessage = null;
        $this->validationReport = null;
        $this->previewText = null;

        try {
            $kontrolleurId = (string) config('messungen.kontrolleur_id', '');
            if ($kontrolleurId === '') {
                $this->errorMessage = 'Kontrolleur-ID ist nicht konfiguriert. Bitte KONTROLLEUR_ID in .env setzen.';
                return;
            }

            $messungen = $this->getMessungenQuery()->get();

            if ($messungen->isEmpty()) {
                $this->errorMessage = 'Keine Messungen für den gewählten Zeitraum/Filter gefunden.';
                return;
            }

            $svc = app(AmtExportService::class);
            $svc->validateKontrolleurId($kontrolleurId);
            $report = $svc->validateAll($messungen);

            // Preview-Text (Header + erste 5 gültige Zeilen)
            $previewLines = [AmtExportService::HEADER_PREFIX . $kontrolleurId];
            foreach ($report['valid']->take(5) as $m) {
                $previewLines[] = $svc->buildLine($m);
            }
            $this->previewText = implode("\n", $previewLines);

            // Invalid-Report: nur erste 50 zur Anzeige
            $invalidPreview = [];
            foreach (array_slice($report['invalid'], 0, 50) as $entry) {
                $m = $entry['messung'];
                $invalidPreview[] = [
                    'id' => $m->id,
                    'kodex' => $m->cIM_CODICE,
                    'datum' => $m->cMIS_DATA2 ?: $m->cMIS_DATA,
                    'stadio' => $m->cMIS_STADIO,
                    'errors' => $entry['errors'],
                ];
            }

            $this->validationReport = [
                'total' => $messungen->count(),
                'valid_count' => $report['valid']->count(),
                'invalid_count' => count($report['invalid']),
                'invalid_preview' => $invalidPreview,
                'invalid_truncated' => count($report['invalid']) > 50,
            ];
        } catch (\Throwable $e) {
            $this->errorMessage = 'Analyse-Fehler: ' . $e->getMessage();
        }
    }

    public function updatedJahr(): void               { $this->analyze(); }
    public function updatedNurNichtExportiert(): void { $this->analyze(); }
    public function updatedVonDatum(): void           { $this->analyze(); }
    public function updatedBisDatum(): void           { $this->analyze(); }

    /**
     * Datei bauen und Download triggern (ohne Mail, ohne Flag setzen).
     */
    public function download()
    {
        if (!$this->validationReport || $this->validationReport['valid_count'] === 0) {
            $this->errorMessage = 'Keine gültigen Messungen zum Export.';
            return null;
        }

        try {
            $kontrolleurId = (string) config('messungen.kontrolleur_id', '');
            $messungen = $this->getValidMessungen();
            $svc = app(AmtExportService::class);
            $content = $svc->buildFile($messungen, $kontrolleurId);
            $filename = $svc->buildFilename($messungen->count());

            return response()->streamDownload(
                fn() => print($content),
                $filename,
                ['Content-Type' => 'text/plain']
            );
        } catch (\Throwable $e) {
            $this->errorMessage = 'Download-Fehler: ' . $e->getMessage();
            return null;
        }
    }

    /**
     * Datei bauen + per E-Mail verschicken + Messungen als exportiert markieren.
     */
    public function send(): void
    {
        $this->errorMessage = null;

        // Empfänger validieren
        $empfaenger = trim($this->empfaenger);
        if ($empfaenger === '' || !filter_var($empfaenger, FILTER_VALIDATE_EMAIL)) {
            $this->errorMessage = 'Bitte eine gültige E-Mail-Adresse eingeben.';
            return;
        }

        if (!$this->validationReport || $this->validationReport['valid_count'] === 0) {
            $this->errorMessage = 'Keine gültigen Messungen zum Export.';
            return;
        }

        try {
            $kontrolleurId = (string) config('messungen.kontrolleur_id', '');
            $messungen = $this->getValidMessungen();
            $svc = app(AmtExportService::class);
            $content = $svc->buildFile($messungen, $kontrolleurId);
            $filename = $svc->buildFilename($messungen->count());

            // Zeitraum-Text
            $zeitraum = $this->vonDatum || $this->bisDatum
                ? sprintf('%s – %s', $this->vonDatum ?: '…', $this->bisDatum ?: '…')
                : "Jahr {$this->jahr}";

            $mail = new MessungenExportMail(
                fileContent: $content,
                fileName: $filename,
                kontrolleurId: $kontrolleurId,
                anzahl: $messungen->count(),
                zeitraum: $zeitraum,
                absenderName: config('messungen.amt_email_from_name'),
            );

            Mail::to($empfaenger)->send($mail);

            // Export-Flag setzen
            $now = now();
            Messung::whereIn('id', $messungen->pluck('id'))
                ->update([
                    'exported_at' => $now,
                    'exported_to_email' => $empfaenger,
                    'exported_kontrolleur_id' => $kontrolleurId,
                ]);

            // Letzte E-Mail merken
            session(['amt_export_empfaenger' => $empfaenger]);

            session()->flash('success', sprintf(
                'Export erfolgreich: %d Messungen an %s verschickt.',
                $messungen->count(), $empfaenger
            ));

            $this->close();
            $this->dispatch('messungen-exported');
        } catch (\Throwable $e) {
            $this->errorMessage = 'Versand fehlgeschlagen: ' . $e->getMessage();
        }
    }

    /**
     * Nur die gültigen Messungen (erneut geladen, frisch).
     */
    protected function getValidMessungen()
    {
        $messungen = $this->getMessungenQuery()->get();
        $svc = app(AmtExportService::class);
        return $svc->validateAll($messungen)['valid'];
    }

    protected function getMessungenQuery()
    {
        $q = Messung::query();

        // Wenn eine explizite ID-Liste übergeben wurde: nur diese Messungen.
        // Filter werden dann ignoriert (Auswahl hat Vorrang).
        if (!empty($this->messungIds)) {
            $q->whereIn('id', $this->messungIds);
            // Nur Messungen mit existierender Anlage
            $q->where('codeInImpianti', '>', 0);
            return $q->orderBy('cIM_CODICE')->orderBy('cMIS_DATA')->orderBy('cMIS_STADIO');
        }

        // Jahr-Filter (wirkt nur wenn keine vonDatum/bisDatum gesetzt)
        if (!$this->vonDatum && !$this->bisDatum && $this->jahr) {
            $q->whereRaw("RIGHT(cMIS_DATA, 4) = ?", [$this->jahr]);
        }

        // Zeitraum (überschreibt Jahr wenn gesetzt)
        if ($this->vonDatum) {
            $von = $this->toDbDate($this->vonDatum);
            if ($von) {
                $q->whereRaw("STR_TO_DATE(cMIS_DATA, '%d%m%Y') >= ?", [$von]);
            }
        }
        if ($this->bisDatum) {
            $bis = $this->toDbDate($this->bisDatum);
            if ($bis) {
                $q->whereRaw("STR_TO_DATE(cMIS_DATA, '%d%m%Y') <= ?", [$bis]);
            }
        }

        if ($this->nurNichtExportiert) {
            $q->whereNull('exported_at');
        }

        // Nur Messungen mit existierender Anlage
        $q->where('codeInImpianti', '>', 0);

        return $q->orderBy('cIM_CODICE')->orderBy('cMIS_DATA')->orderBy('cMIS_STADIO');
    }

    /**
     * dd.MM.yyyy oder yyyy-MM-dd → yyyy-MM-dd
     */
    private function toDbDate(string $s): ?string
    {
        $s = trim($s);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s)) {
            return $s;
        }
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $s, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        return null;
    }

    public function render()
    {
        return view('livewire.messungen.amt-export');
    }
}
