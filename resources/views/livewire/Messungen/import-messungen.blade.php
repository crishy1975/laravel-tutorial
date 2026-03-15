{{-- resources/views/livewire/messungen/import-messungen.blade.php --}}
<div class="container-fluid py-2 py-md-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-0">
                <i class="bi bi-speedometer2 text-primary"></i>
                Messdaten importieren
            </h1>
            <p class="text-muted mb-0 small d-none d-md-block">Wöhler XML-Format</p>
        </div>
        <a href="{{ route('messungen.anlagen.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-sm-inline">Zurück</span>
        </a>
    </div>

    {{-- Upload-Karte --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0"><i class="bi bi-upload"></i> XML-Datei hochladen</h6>
        </div>
        <div class="card-body">
            <form wire:submit="import">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-5">
                        <label for="messFile" class="form-label small mb-1">XML-Datei auswählen</label>
                        <input type="file" id="messFile" wire:model="messFile" accept=".xml"
                               class="form-control form-control-sm">
                        @error('messFile')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <div wire:loading wire:target="messFile" class="text-muted small mt-1">
                            <span class="spinner-border spinner-border-sm"></span> Datei wird hochgeladen...
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="button" wire:click="generatePreview" class="btn btn-outline-info btn-sm"
                                wire:loading.attr="disabled" wire:target="generatePreview"
                                @if(!$messFile) disabled @endif>
                            <span wire:loading.remove wire:target="generatePreview">
                                <i class="bi bi-eye"></i> Vorschau
                            </span>
                            <span wire:loading wire:target="generatePreview">
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm"
                                wire:loading.attr="disabled" wire:target="import"
                                @if(!$messFile) disabled @endif>
                            <span wire:loading.remove wire:target="import">
                                <i class="bi bi-upload"></i> Importieren
                            </span>
                            <span wire:loading wire:target="import">
                                <span class="spinner-border spinner-border-sm"></span> Importiere...
                            </span>
                        </button>
                    </div>
                    @if($importResult || count($importErrors) > 0 || $showPreview)
                        <div class="col-auto">
                            <button type="button" wire:click="resetImport" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Instrument-Info --}}
    @if($instrumentInfo)
        <div class="alert alert-info py-2 mb-3" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle me-2"></i>
                <div class="small">
                    <strong>Messgerät:</strong> {{ $instrumentInfo['manufacturer'] }} {{ $instrumentInfo['type'] }}
                    (S/N: {{ $instrumentInfo['serialNumber'] }})
                    &bull; <strong>{{ $instrumentInfo['numMeasurements'] }}</strong> Messungen
                    &bull; <strong>{{ $instrumentInfo['numCustomers'] }}</strong> Kunden
                    @if($instrumentInfo['lastCheckDate'])
                        &bull; Letzte Prüfung: {{ $instrumentInfo['lastCheckDate'] }}
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Vorschau --}}
    @if($showPreview && count($preview) > 0)
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-info bg-opacity-10 py-2">
                <h6 class="mb-0 text-info">
                    <i class="bi bi-eye"></i> Vorschau (erste {{ count($preview) }} Datensätze)
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Kodex</th>
                            <th>Datum</th>
                            <th>Brennstoff</th>
                            <th class="text-end">O₂ %</th>
                            <th class="text-end">CO₂ %</th>
                            <th class="text-end">CO mg/m³</th>
                            <th class="text-end">NOx mg/m³</th>
                            <th class="text-end">Abg.T °C</th>
                            <th class="text-end">Verl. %</th>
                            <th class="text-center">Anlage?</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview as $row)
                            <tr>
                                <td class="font-monospace">{{ $row['cIM_CODICE'] }}</td>
                                <td>{{ $row['cMIS_DATA2'] ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $row['cMIS_COMBUSTIBILE_P'] }}</span>
                                </td>
                                <td class="text-end">{{ $row['cMIS_OSSIGENO'] ?: '-' }}</td>
                                <td class="text-end">{{ $row['cMIS_ANIDRIDE_CARBONICA'] ?: '-' }}</td>
                                <td class="text-end">{{ (int)($row['cMIS_MONOSSSIDO'] ?? 0) ?: '-' }}</td>
                                <td class="text-end">{{ (int)($row['cMIS_BIOSSIDO_AZOTO'] ?? 0) ?: '-' }}</td>
                                <td class="text-end">{{ (int)($row['cMIS_T_GAS_COMB'] ?? 0) ?: '-' }}</td>
                                <td class="text-end">{{ $row['cMIS_PERD_FUMI'] ?: '-' }}</td>
                                <td class="text-center">
                                    @if($row['codeInImpianti'] > 0)
                                        <i class="bi bi-check-circle text-success" title="Anlage gefunden"></i>
                                    @else
                                        <i class="bi bi-x-circle text-danger" title="Anlage nicht in DB"></i>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Ergebnis --}}
    @if($importResult)
        <div class="alert alert-success py-2" role="alert">
            <h6 class="alert-heading mb-2">
                <i class="bi bi-check-circle"></i> Import abgeschlossen
            </h6>
            <div class="row g-2">
                <div class="col-auto">
                    <span class="badge bg-secondary">Verarbeitet: <strong>{{ $importResult['total'] }}</strong></span>
                </div>
                <div class="col-auto">
                    <span class="badge bg-success">Importiert: <strong>{{ $importResult['imported'] }}</strong></span>
                </div>
                <div class="col-auto">
                    <span class="badge bg-warning text-dark">Übersprungen: <strong>{{ $importResult['skipped'] }}</strong></span>
                </div>
                @if($importResult['ohneAnlage'] > 0)
                    <div class="col-auto">
                        <span class="badge bg-info">Ohne Anlage: <strong>{{ $importResult['ohneAnlage'] }}</strong></span>
                    </div>
                @endif
                @if($importResult['errors'] > 0)
                    <div class="col-auto">
                        <span class="badge bg-danger">Fehler: <strong>{{ $importResult['errors'] }}</strong></span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Import-Fehler --}}
    @if(count($importErrors) > 0)
        <div class="alert alert-danger py-2" role="alert">
            <h6 class="alert-heading mb-2">
                <i class="bi bi-exclamation-triangle"></i> Fehler beim Import
            </h6>
            <div style="max-height: 200px; overflow-y: auto;">
                @foreach($importErrors as $importError)
                    <div class="small">{{ $importError }}</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Info-Box: Format-Erklärung --}}
    <div class="card shadow-sm">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Unterstütztes Format</h6>
        </div>
        <div class="card-body py-2">
            <div class="small text-muted">
                <p class="mb-2">
                    <strong>Wöhler XML-Format</strong> - Export vom Messgerät (z.B. Wöhler A 450 L)
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Unterstützte Messtypen:</strong></p>
                        <ul class="mb-0 ps-3">
                            <li><code>CombustionMeasurement</code> - Standard-Verbrennungsmessung</li>
                            <li><code>Uni10389_1Measurement</code> - UNI 10389-1</li>
                            <li><code>Uni10389_2Measurement</code> - UNI 10389-2 (Biomasse)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Importierte Werte:</strong></p>
                        <ul class="mb-0 ps-3">
                            <li>O₂, CO₂, CO, NOx (mg/m³), Abgastemperatur</li>
                            <li>Abgasverlust, Rußzahl, Ölspuren</li>
                            <li>Brennstoff (Gas, Öl, Pellet, Holz)</li>
                        </ul>
                    </div>
                </div>
                <hr class="my-2">
                <p class="mb-1"><i class="bi bi-dot"></i> Bereits vorhandene Messungen (gleicher Kodex + Datum + Uhrzeit) werden übersprungen</p>
                <p class="mb-1"><i class="bi bi-dot"></i> Das Ergebnis (positiv/negativ) wird automatisch anhand der Grenzwerte berechnet</p>
                <p class="mb-0"><i class="bi bi-dot"></i> Messungen werden auch importiert, wenn die Anlage nicht in der DB existiert</p>
            </div>
        </div>
    </div>
</div>
