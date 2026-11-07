{{--
════════════════════════════════════════════════════════════════════════════
DATEI: tagesinkassi-import.blade.php
PFAD:  resources/views/livewire/tagesinkassi/tagesinkassi-import.blade.php
════════════════════════════════════════════════════════════════════════════
--}}
@php
    $eur = fn($v) => number_format((float) $v, 2, ',', '.') . ' €';
    $statusInfo = [
        'neu'        => ['bg-success',            'Neu'],
        'ergaenzen'  => ['bg-primary',            'Ergänzen'],
        'gleich'     => ['bg-secondary',          'Schon erfasst'],
        'abweichend' => ['bg-warning text-dark',  'Abweichend'],
    ];
@endphp

<div class="container-fluid py-2 py-md-4">

    <style>
        .import-tabelle td, .import-tabelle th { vertical-align: middle; }
        .import-tabelle .betrag { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .import-tabelle tr.abgewaehlt td { opacity: 0.5; }
    </style>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h5 mb-0"><i class="bi bi-upload text-primary"></i> POS-Import</h1>
            <p class="text-muted mb-0 small">Kartenzahlungen aus dem POS-Portal als Tagesinkassi übernehmen</p>
        </div>
        <a href="{{ route('tagesinkassi.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Zurück zu Tagesinkassi
        </a>
    </div>

    {{-- Upload --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label small mb-1">Export-Datei (account_transactions_….xlsx)</label>
                    <input type="file" wire:model="datei" accept=".xlsx,.xls,.csv"
                           class="form-control form-control-sm @error('datei') is-invalid @enderror">
                    @error('datei') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Verbuchen als</label>
                    <select wire:model.live="mwstSatz" class="form-select form-select-sm">
                        <option value="10">Corrispettivi 10 %</option>
                        <option value="22">Corrispettivi 22 %</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <div wire:loading wire:target="datei" class="text-muted small">
                        <span class="spinner-border spinner-border-sm"></span> Datei wird gelesen…
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($fehler)
        <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle"></i> {{ $fehler }}</div>
    @endif

    @if($zahlungenGesamt > 0 || !empty($ignoriert))
        {{-- Zusammenfassung --}}
        <div class="alert alert-light border py-2 small">
            <strong>{{ $zahlungenGesamt }}</strong> Kartenzahlungen in der Datei
            @if($bereitsImportiert > 0)
                – davon <strong>{{ $bereitsImportiert }}</strong> bereits importiert (werden übersprungen)
            @endif
            @if(!empty($ignoriert))
                <br>
                <span class="text-muted">
                    Nicht übernommen:
                    @foreach($ignoriert as $art => $anzahl)
                        {{ $anzahl }} {{ $art }}@if(!$loop->last), @endif
                    @endforeach
                </span>
            @endif
        </div>
    @endif

    @if(!empty($tage))
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0"><i class="bi bi-list-check"></i> Vorschau – {{ count($tage) }} Tage</h6>
                <div class="d-flex gap-1">
                    <button wire:click="alleAuswaehlen(true)" class="btn btn-sm btn-outline-secondary">Alle</button>
                    <button wire:click="alleAuswaehlen(false)" class="btn btn-sm btn-outline-secondary">Keine</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 import-tabelle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th style="min-width: 200px;">Datum</th>
                                <th>Zahlungen</th>
                                <th class="betrag">POS-Summe</th>
                                <th class="betrag">Bereits erfasst</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tage as $i => $t)
                                @php [$badge, $label] = $statusInfo[$t['status']]; @endphp
                                <tr wire:key="tag-{{ $t['datum'] }}" class="{{ $t['auswahl'] ? '' : 'abgewaehlt' }}">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input" wire:model.live="tage.{{ $i }}.auswahl">
                                    </td>
                                    <td class="text-nowrap">
                                        {{ \Carbon\Carbon::parse($t['datum'])->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
                                    </td>
                                    <td class="small">
                                        @foreach($t['transaktionen'] as $z)
                                            <div>
                                                {{ \Carbon\Carbon::parse($z['datum_zeit'])->format('H:i') }}
                                                – {{ $eur($z['betrag']) }}
                                                <span class="text-muted">{{ $z['terminal'] }} {{ $z['karte'] }}</span>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="betrag fw-semibold">{{ $eur($t['summe']) }}</td>
                                    <td class="betrag">{{ $t['bestehend'] !== null ? $eur($t['bestehend']) : '–' }}</td>
                                    <td>
                                        <span class="badge {{ $badge }}">{{ $label }}</span>
                                        <div class="small text-muted">
                                            @switch($t['status'])
                                                @case('neu') Eintrag wird angelegt @break
                                                @case('ergaenzen') Betrag wird addiert @break
                                                @case('gleich') Nur verknüpfen, Betrag bleibt @break
                                                @case('abweichend') Anhaken = POS-Betrag addieren @break
                                            @endswitch
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="small">
                    {{ $auswahlTage }} Tage ausgewählt – neu zu verbuchen: <strong>{{ $eur($auswahlSumme) }}</strong>
                    ({{ $mwstSatz }} %)
                </span>
                <button wire:click="importieren" class="btn btn-success btn-sm"
                        wire:loading.attr="disabled" wire:target="importieren"
                        @disabled($auswahlTage === 0)>
                    <span wire:loading.remove wire:target="importieren"><i class="bi bi-check-lg"></i> Importieren</span>
                    <span wire:loading wire:target="importieren"><span class="spinner-border spinner-border-sm"></span> Importiere…</span>
                </button>
            </div>
        </div>
    @elseif($zahlungenGesamt > 0 && $bereitsImportiert === $zahlungenGesamt)
        <div class="alert alert-success py-2">
            <i class="bi bi-check-circle"></i> Alle Kartenzahlungen dieser Datei sind bereits importiert.
        </div>
    @endif

</div>
