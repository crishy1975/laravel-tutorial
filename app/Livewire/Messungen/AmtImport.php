<?php

namespace App\Livewire\Messungen;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Services\AmtImportService;

#[Layout('layouts.app')]
class AmtImport extends Component
{
    use WithFileUploads;

    public $datei = null;

    public ?array $parseResult = null;
    public ?string $errorMessage = null;
    public ?string $kontrolleurId = null;

    // Aktionen pro Zeile: key = line_no, value = 'skip'|'update'|'insert'
    public array $actions = [];

    // Default-Aktion für Duplikate
    public string $defaultAction = 'skip'; // skip | update

    public ?array $commitStats = null;

    protected $rules = [
        'datei' => 'required|file|mimes:txt|max:2048',
    ];

    public function updatedDatei(): void
    {
        $this->errorMessage = null;
        $this->parseResult = null;
        $this->commitStats = null;

        if (!$this->datei) return;

        $this->analyze();
    }

    public function analyze(): void
    {
        $this->errorMessage = null;
        $this->parseResult = null;
        $this->commitStats = null;

        if (!$this->datei) {
            $this->errorMessage = 'Keine Datei ausgewählt.';
            return;
        }

        try {
            $content = file_get_contents($this->datei->getRealPath());
            $svc = app(AmtImportService::class);
            $result = $svc->parseFile($content);

            if ($result['header_error']) {
                $this->errorMessage = $result['header_error'];
                return;
            }

            $this->kontrolleurId = $result['kontrolleur_id'];
            $this->parseResult = $result;

            // Default-Aktionen setzen
            $this->actions = [];
            foreach ($result['rows'] as $row) {
                if (!empty($row['errors'])) {
                    $this->actions[$row['line_no']] = 'skip';
                } elseif ($row['existing']) {
                    $this->actions[$row['line_no']] = $this->defaultAction;
                } else {
                    $this->actions[$row['line_no']] = 'insert';
                }
            }
        } catch (\Throwable $e) {
            $this->errorMessage = 'Parse-Fehler: ' . $e->getMessage();
        }
    }

    public function updatedDefaultAction(): void
    {
        if (!$this->parseResult) return;
        foreach ($this->parseResult['rows'] as $row) {
            if (!empty($row['errors'])) continue;
            if ($row['existing']) {
                $this->actions[$row['line_no']] = $this->defaultAction;
            }
        }
    }

    public function commit(): void
    {
        if (!$this->parseResult) {
            $this->errorMessage = 'Keine Daten zum Import.';
            return;
        }

        try {
            // Actions in rows mergen
            $rows = [];
            foreach ($this->parseResult['rows'] as $row) {
                $row['action'] = $this->actions[$row['line_no']] ?? 'skip';
                $rows[] = $row;
            }

            $svc = app(AmtImportService::class);
            $this->commitStats = $svc->commit($rows, $this->defaultAction);

            session()->flash('success', sprintf(
                'Import abgeschlossen: %d neu, %d aktualisiert, %d übersprungen.',
                $this->commitStats['inserted'],
                $this->commitStats['updated'],
                $this->commitStats['skipped']
            ));
        } catch (\Throwable $e) {
            $this->errorMessage = 'Import-Fehler: ' . $e->getMessage();
        }
    }

    public function reset2(): void
    {
        $this->datei = null;
        $this->parseResult = null;
        $this->errorMessage = null;
        $this->commitStats = null;
        $this->actions = [];
        $this->kontrolleurId = null;
    }

    public function getStatsProperty(): array
    {
        if (!$this->parseResult) {
            return ['total' => 0, 'ok' => 0, 'errors' => 0, 'new' => 0, 'existing' => 0];
        }
        $rows = $this->parseResult['rows'];
        $ok = 0; $errors = 0; $new = 0; $existing = 0;
        foreach ($rows as $r) {
            if (!empty($r['errors'])) $errors++;
            else {
                $ok++;
                if ($r['existing']) $existing++;
                else $new++;
            }
        }
        return [
            'total' => count($rows),
            'ok' => $ok,
            'errors' => $errors,
            'new' => $new,
            'existing' => $existing,
        ];
    }

    public function render()
    {
        return view('livewire.messungen.amt-import', [
            'stats' => $this->stats,
        ]);
    }
}
