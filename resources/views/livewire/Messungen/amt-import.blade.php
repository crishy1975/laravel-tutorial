{{-- resources/views/livewire/messungen/amt-import.blade.php --}}
<div class="container-fluid py-3">
    <h3 class="mb-3">
        <i class="bi bi-envelope-arrow-down"></i>
        Amt-Import — Messungen aus Datei einlesen
    </h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Upload --}}
    <div class="card mb-3">
        <div class="card-body">
            <label class="form-label">Amt-Export-Datei (.txt)</label>
            <input type="file" class="form-control" wire:model="datei" accept=".txt">
            @error('datei') <div class="text-danger small">{{ $message }}</div> @enderror

            <div wire:loading wire:target="datei" class="text-muted small mt-2">
                <i class="bi bi-hourglass-split"></i> Datei wird hochgeladen …
            </div>
        </div>
    </div>

    @if($errorMessage)
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> {{ $errorMessage }}
        </div>
    @endif

    @if($parseResult)

        {{-- Kontrolleur --}}
        <div class="alert alert-info py-2">
            <i class="bi bi-person-badge"></i>
            <strong>Kontrolleur-ID aus Datei:</strong> <code>{{ $kontrolleurId }}</code>
        </div>

        {{-- Stats --}}
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body py-2 text-center">
                        <div class="text-muted small">Gesamt</div>
                        <div class="fs-3 fw-bold">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body py-2 text-center">
                        <div class="text-primary small">Neu</div>
                        <div class="fs-3 fw-bold text-primary">{{ $stats['new'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body py-2 text-center">
                        <div class="text-warning small">Bereits vorhanden</div>
                        <div class="fs-3 fw-bold text-warning">{{ $stats['existing'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card {{ $stats['errors'] > 0 ? 'border-danger' : 'border-secondary' }}">
                    <div class="card-body py-2 text-center">
                        <div class="{{ $stats['errors'] > 0 ? 'text-danger' : 'text-muted' }} small">Fehler</div>
                        <div class="fs-3 fw-bold {{ $stats['errors'] > 0 ? 'text-danger' : '' }}">
                            {{ $stats['errors'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Default-Aktion für Duplikate --}}
        @if($stats['existing'] > 0)
            <div class="card mb-3">
                <div class="card-body py-2">
                    <label class="form-label mb-1"><strong>Bei bereits vorhandenen Messungen:</strong></label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" wire:model.live="defaultAction" value="skip" id="da-skip">
                        <label class="form-check-label" for="da-skip">Überspringen</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" wire:model.live="defaultAction" value="update" id="da-update">
                        <label class="form-check-label" for="da-update">Überschreiben</label>
                    </div>
                    <span class="small text-muted ms-2">(kann pro Zeile einzeln angepasst werden)</span>
                </div>
            </div>
        @endif

        {{-- Zeilen-Tabelle --}}
        <div class="card mb-3">
            <div class="card-header py-2">
                <strong>Vorschau</strong>
                <span class="small text-muted ms-2">
                    Key = Kodex + Datum + Stadio + Typ
                </span>
            </div>
            <div class="table-responsive" style="max-height: 500px;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Kodex</th>
                            <th>Datum</th>
                            <th>Stadio</th>
                            <th>Brennstoff</th>
                            <th>CO</th>
                            <th>NOx</th>
                            <th>Esito</th>
                            <th>Status</th>
                            <th style="width: 130px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parseResult['rows'] as $row)
                            @php
                                $hasError = !empty($row['errors']);
                                $rowClass = $hasError ? 'table-danger' : ($row['existing'] ? 'table-warning' : '');
                                $d = $row['data'];
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="small">{{ $row['line_no'] }}</td>
                                <td><code>{{ $d['cIM_CODICE'] ?? '—' }}</code></td>
                                <td class="small">{{ $d['cMIS_DATA2'] ?? $d['cMIS_DATA'] ?? '—' }}</td>
                                <td class="text-center">{{ $d['cMIS_STADIO'] ?? '—' }}</td>
                                <td class="small">{{ $d['cMIS_COMBUSTIBILE_P'] ?? '—' }}</td>
                                <td>{{ $d ? (int)$d['cMIS_MONOSSSIDO'] : '—' }}</td>
                                <td>{{ $d ? (int)$d['cMIS_BIOSSIDO_AZOTO'] : '—' }}</td>
                                <td class="text-center">
                                    @if(($d['strEsito'] ?? null) === '1')
                                        <span class="badge bg-success">OK</span>
                                    @elseif(($d['strEsito'] ?? null) === '0')
                                        <span class="badge bg-danger">NEG</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($hasError)
                                        <span class="badge bg-danger"
                                              title="{{ implode(' | ', $row['errors']) }}">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            {{ count($row['errors']) }} Fehler
                                        </span>
                                    @elseif($row['existing'])
                                        <span class="badge bg-warning text-dark">vorhanden</span>
                                    @else
                                        <span class="badge bg-primary">neu</span>
                                    @endif
                                </td>
                                <td>
                                    @if($hasError)
                                        <span class="small text-muted">überspringen</span>
                                    @elseif($row['existing'])
                                        <select class="form-select form-select-sm"
                                                wire:model="actions.{{ $row['line_no'] }}">
                                            <option value="skip">überspringen</option>
                                            <option value="update">überschreiben</option>
                                        </select>
                                    @else
                                        <span class="small text-primary">einfügen</span>
                                    @endif
                                </td>
                            </tr>
                            @if($hasError)
                                <tr class="{{ $rowClass }}">
                                    <td colspan="10" class="small text-danger ps-4">
                                        <ul class="mb-0">
                                            @foreach($row['errors'] as $err)
                                                <li>{{ $err }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Commit --}}
        @if(!$commitStats)
            <div class="d-flex justify-content-between">
                <button class="btn btn-secondary" wire:click="reset2">
                    <i class="bi bi-x-circle"></i> Abbrechen
                </button>
                <button class="btn btn-primary" wire:click="commit"
                        @if($stats['ok'] === 0) disabled @endif>
                    <i class="bi bi-database-down"></i>
                    Import ausführen ({{ $stats['ok'] }} gültige Zeilen)
                </button>
            </div>
        @else
            <div class="alert alert-success">
                <h5><i class="bi bi-check-circle"></i> Import abgeschlossen</h5>
                <ul class="mb-0">
                    <li><strong>{{ $commitStats['inserted'] }}</strong> neue Messungen angelegt</li>
                    <li><strong>{{ $commitStats['updated'] }}</strong> bestehende überschrieben</li>
                    <li><strong>{{ $commitStats['skipped'] }}</strong> übersprungen</li>
                </ul>
                @if(!empty($commitStats['errors']))
                    <hr>
                    <strong>Fehler:</strong>
                    <ul class="mb-0 small">
                        @foreach($commitStats['errors'] as $err)
                            <li class="text-danger">{{ $err }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <button class="btn btn-secondary" wire:click="reset2">
                <i class="bi bi-arrow-clockwise"></i> Weitere Datei importieren
            </button>
        @endif
    @endif
</div>
