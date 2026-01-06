{{--
════════════════════════════════════════════════════════════════════════════
DATEI: eingangsrechnungen-verwaltung.blade.php
PFAD:  resources/views/livewire/admin/eingangsrechnungen-verwaltung.blade.php
════════════════════════════════════════════════════════════════════════════
--}}

<div>
    {{-- ═══════════════════════════════════════════════════════════
         HEADER
         ═══════════════════════════════════════════════════════════ --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="bi bi-receipt me-2"></i><span class="d-none d-sm-inline">Eingangsrechnungen</span><span class="d-sm-none">Rechnungen</span>
        </h4>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         STATISTIK-KARTEN (bereits mobile-optimiert)
         ═══════════════════════════════════════════════════════════ --}}
    <div class="row g-2 mb-3">
        <div class="col-4 col-md-2">
            <div class="card bg-light h-100">
                <div class="card-body py-2 px-2 text-center">
                    <div class="text-muted small">Gesamt</div>
                    <div class="fs-5 fw-bold">{{ $statistik['gesamt'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-2">
            <div class="card bg-warning bg-opacity-25 h-100">
                <div class="card-body py-2 px-2 text-center">
                    <div class="text-muted small">Offen</div>
                    <div class="fs-5 fw-bold text-warning">{{ $statistik['offen'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-2">
            <div class="card bg-success bg-opacity-25 h-100">
                <div class="card-body py-2 px-2 text-center">
                    <div class="text-muted small">Bezahlt</div>
                    <div class="fs-5 fw-bold text-success">{{ $statistik['bezahlt'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-danger bg-opacity-25 h-100">
                <div class="card-body py-2 px-2 text-center">
                    <div class="text-muted small">Summe offen</div>
                    <div class="fs-6 fw-bold text-danger">€ {{ number_format($statistik['summe_offen'], 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-info bg-opacity-25 h-100">
                <div class="card-body py-2 px-2 text-center">
                    <div class="text-muted small">Lieferanten</div>
                    <div class="fs-5 fw-bold text-info">{{ $statistik['lieferanten'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         TOOLBAR: IMPORT & ANSICHT (Mobile-optimiert)
         ═══════════════════════════════════════════════════════════ --}}
    <div class="card mb-3">
        <div class="card-body py-2 px-2 px-md-3">
            {{-- Ansicht-Toggle (immer oben) --}}
            <div class="btn-group btn-group-sm w-100 mb-2">
                <button class="btn {{ $ansicht === 'rechnungen' ? 'btn-dark' : 'btn-outline-dark' }}"
                        wire:click="$set('ansicht', 'rechnungen')">
                    <i class="bi bi-list-ul me-1"></i>Rechnungen
                </button>
                <button class="btn {{ $ansicht === 'lieferanten' ? 'btn-dark' : 'btn-outline-dark' }}"
                        wire:click="$set('ansicht', 'lieferanten')">
                    <i class="bi bi-building me-1"></i>Lieferanten
                </button>
            </div>

            {{-- Import --}}
            <div class="input-group input-group-sm mb-2">
                <input type="file" 
                       wire:model="uploadDatei" 
                       class="form-control" 
                       accept=".xml,.zip"
                       id="uploadInput">
                <button class="btn btn-primary" 
                        wire:click="importStarten"
                        wire:loading.attr="disabled"
                        wire:target="uploadDatei,importStarten"
                        @if(!$uploadDatei) disabled @endif>
                    <span wire:loading.remove wire:target="importStarten">
                        <i class="bi bi-upload"></i><span class="d-none d-sm-inline ms-1">Import</span>
                    </span>
                    <span wire:loading wire:target="uploadDatei,importStarten">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </button>
            </div>
            @error('uploadDatei')
                <small class="text-danger d-block mb-2">{{ $message }}</small>
            @enderror

            {{-- Filter (nur bei Rechnungen) --}}
            @if($ansicht === 'rechnungen')
                <div class="row g-2">
                    <div class="col-8">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" 
                                   class="form-control" 
                                   placeholder="Suchen..."
                                   wire:model.live.debounce.500ms="suchbegriff">
                        </div>
                    </div>
                    <div class="col-4">
                        <select class="form-select form-select-sm" wire:model.live="filterStatus">
                            <option value="">Alle</option>
                            <option value="offen">Offen</option>
                            <option value="bezahlt">Bezahlt</option>
                            <option value="ignoriert">Ign.</option>
                        </select>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Aktiver Filter-Hinweis --}}
    @if($filterLieferant && $ansicht === 'rechnungen')
        @php $aktiverLieferant = \App\Models\Lieferant::find($filterLieferant); @endphp
        @if($aktiverLieferant)
            <div class="alert alert-info py-2 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <small>
                        <i class="bi bi-funnel me-1"></i>
                        <strong>{{ $aktiverLieferant->name }}</strong>
                    </small>
                    <button class="btn btn-sm btn-outline-info py-0" wire:click="filterZuruecksetzen">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        @endif
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         ANSICHT: RECHNUNGEN
         ═══════════════════════════════════════════════════════════ --}}
    @if($ansicht === 'rechnungen')
    
        {{-- DESKTOP: Tabelle (ab md) --}}
        <div class="card d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 90px;" role="button" wire:click="sortieren('rechnungsdatum')">
                                Datum
                                @if($sortierSpalte === 'rechnungsdatum')
                                    <i class="bi bi-chevron-{{ $sortierRichtung === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th role="button" wire:click="sortieren('lieferant')">
                                Lieferant
                                @if($sortierSpalte === 'lieferant')
                                    <i class="bi bi-chevron-{{ $sortierRichtung === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th>Rechnungsnr.</th>
                            <th class="text-end" role="button" wire:click="sortieren('brutto_betrag')">
                                Betrag
                                @if($sortierSpalte === 'brutto_betrag')
                                    <i class="bi bi-chevron-{{ $sortierRichtung === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th class="text-center">Status</th>
                            <th style="width: 160px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rechnungen as $rechnung)
                            <tr class="{{ $rechnung->istUeberfaellig() ? 'table-danger' : ($rechnung->istGutschrift() ? 'table-info' : '') }}">
                                <td><small>{{ $rechnung->rechnungsdatum->format('d.m.Y') }}</small></td>
                                <td>
                                    <strong>{{ $rechnung->lieferant->name }}</strong>
                                    @if(!$rechnung->lieferant->hatIban())
                                        <i class="bi bi-exclamation-circle text-warning" title="Keine IBAN"></i>
                                    @endif
                                </td>
                                <td>
                                    <a href="#" wire:click.prevent="detailAnzeigen({{ $rechnung->id }})" class="text-decoration-none">
                                        {{ $rechnung->rechnungsnummer }}
                                    </a>
                                    @if($rechnung->istGutschrift())
                                        <span class="badge bg-info ms-1">Gutschrift</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $rechnung->istGutschrift() ? 'text-success' : '' }}">
                                    € {{ number_format($rechnung->brutto_betrag, 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $rechnung->status_badge_class }}">
                                        {{ ucfirst($rechnung->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($rechnung->status === 'offen')
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-success btn-sm" wire:click="schnellBezahlt({{ $rechnung->id }}, 'bank')" title="Bank">
                                                <i class="bi bi-bank"></i>
                                            </button>
                                            <button class="btn btn-success btn-sm" wire:click="schnellBezahlt({{ $rechnung->id }}, 'karte')" title="Karte">
                                                <i class="bi bi-credit-card"></i>
                                            </button>
                                            <button class="btn btn-success btn-sm" wire:click="schnellBezahlt({{ $rechnung->id }}, 'bar')" title="Bar">
                                                <i class="bi bi-cash-stack"></i>
                                            </button>
                                            <button class="btn btn-secondary btn-sm" wire:click="schnellIgnoriert({{ $rechnung->id }})" title="Ignorieren">
                                                <i class="bi bi-dash-circle"></i>
                                            </button>
                                        </div>
                                    @else
                                        <button class="btn btn-outline-secondary btn-sm" wire:click="schnellWiederOeffnen({{ $rechnung->id }})" title="Öffnen">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    @endif
                                    <button class="btn btn-outline-primary btn-sm" wire:click="rechnungBearbeiten({{ $rechnung->id }})" title="Bearbeiten">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Keine Rechnungen gefunden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($filterSummen['anzahl'] > 0)
                        <tfoot class="table-light">
                            <tr class="fw-bold small">
                                <td colspan="3" class="text-end">{{ $filterSummen['anzahl'] }} Rechnungen:</td>
                                <td class="text-end">€ {{ number_format($filterSummen['gesamt'], 2, ',', '.') }}</td>
                                <td colspan="2" class="text-center">
                                    <span class="text-warning">{{ number_format($filterSummen['offen'], 2, ',', '.') }}</span> /
                                    <span class="text-success">{{ number_format($filterSummen['bezahlt'], 2, ',', '.') }}</span>
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @if($rechnungen->hasPages())
                <div class="card-footer py-2">
                    {{ $rechnungen->links() }}
                </div>
            @endif
        </div>

        {{-- MOBILE: Cards (unter md) --}}
        <div class="d-md-none">
            @forelse($rechnungen as $rechnung)
                <div class="card mb-2 {{ $rechnung->istUeberfaellig() ? 'border-danger' : ($rechnung->istGutschrift() ? 'border-info' : '') }}">
                    <div class="card-body py-2 px-3">
                        {{-- Kopfzeile: Lieferant + Betrag --}}
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <strong class="d-block">{{ $rechnung->lieferant->name }}</strong>
                                <small class="text-muted">
                                    {{ $rechnung->rechnungsdatum->format('d.m.Y') }} · 
                                    <a href="#" wire:click.prevent="detailAnzeigen({{ $rechnung->id }})" class="text-decoration-none">
                                        {{ $rechnung->rechnungsnummer }}
                                    </a>
                                    @if($rechnung->istGutschrift())
                                        <span class="badge bg-info">Gutschrift</span>
                                    @endif
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold fs-5 {{ $rechnung->istGutschrift() ? 'text-success' : '' }}">€ {{ number_format($rechnung->brutto_betrag, 2, ',', '.') }}</span>
                                <br>
                                <span class="badge {{ $rechnung->status_badge_class }}">
                                    {{ ucfirst($rechnung->status) }}
                                </span>
                            </div>
                        </div>
                        
                        {{-- Aktionen --}}
                        <div class="d-flex gap-1 mt-2">
                            @if($rechnung->status === 'offen')
                                <button class="btn btn-success btn-sm flex-fill" wire:click="schnellBezahlt({{ $rechnung->id }}, 'bank')">
                                    <i class="bi bi-bank"></i> Bank
                                </button>
                                <button class="btn btn-success btn-sm flex-fill" wire:click="schnellBezahlt({{ $rechnung->id }}, 'karte')">
                                    <i class="bi bi-credit-card"></i> Karte
                                </button>
                                <button class="btn btn-success btn-sm flex-fill" wire:click="schnellBezahlt({{ $rechnung->id }}, 'bar')">
                                    <i class="bi bi-cash-stack"></i> Bar
                                </button>
                                <button class="btn btn-secondary btn-sm" wire:click="schnellIgnoriert({{ $rechnung->id }})">
                                    <i class="bi bi-dash-circle"></i>
                                </button>
                            @else
                                <button class="btn btn-outline-secondary btn-sm flex-fill" wire:click="schnellWiederOeffnen({{ $rechnung->id }})">
                                    <i class="bi bi-arrow-counterclockwise"></i> Wieder öffnen
                                </button>
                            @endif
                            <button class="btn btn-outline-primary btn-sm" wire:click="rechnungBearbeiten({{ $rechnung->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Keine Rechnungen gefunden.
                </div>
            @endforelse

            {{-- Mobile Summen --}}
            @if($filterSummen['anzahl'] > 0)
                <div class="card bg-light mt-2">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $filterSummen['anzahl'] }} Rechnungen</span>
                            <span class="fw-bold">€ {{ number_format($filterSummen['gesamt'], 2, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-warning"><i class="bi bi-hourglass-split"></i> Offen</span>
                            <span>€ {{ number_format($filterSummen['offen'], 2, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-success"><i class="bi bi-check-circle"></i> Bezahlt</span>
                            <span>€ {{ number_format($filterSummen['bezahlt'], 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Mobile Pagination --}}
            @if($rechnungen->hasPages())
                <div class="mt-3">
                    {{ $rechnungen->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         ANSICHT: LIEFERANTEN
         ═══════════════════════════════════════════════════════════ --}}
    @if($ansicht === 'lieferanten')
    
        {{-- DESKTOP: Tabelle (ab md) --}}
        <div class="card d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Lieferant</th>
                            <th>P.IVA</th>
                            <th class="text-center">Rechnungen</th>
                            <th class="text-center">Offen</th>
                            <th class="text-end">Summe offen</th>
                            <th class="text-end">Summe gesamt</th>
                            <th>IBAN</th>
                            <th style="width: 100px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lieferanten as $lieferant)
                            <tr class="{{ $lieferant->offene_rechnungen_count > 0 ? 'table-warning' : '' }}">
                                <td><strong>{{ $lieferant->name }}</strong></td>
                                <td><small class="text-muted">{{ $lieferant->partita_iva }}</small></td>
                                <td class="text-center">{{ $lieferant->eingangsrechnungen_count }}</td>
                                <td class="text-center">
                                    @if($lieferant->offene_rechnungen_count > 0)
                                        <span class="badge bg-warning text-dark">{{ $lieferant->offene_rechnungen_count }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($lieferant->summe_offen > 0)
                                        <strong class="text-danger">€ {{ number_format($lieferant->summe_offen ?? 0, 2, ',', '.') }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">€ {{ number_format($lieferant->summe_gesamt ?? 0, 2, ',', '.') }}</td>
                                <td>
                                    @if($lieferant->iban)
                                        <code class="small">{{ $lieferant->iban_formatiert }}</code>
                                    @else
                                        <span class="text-warning"><i class="bi bi-exclamation-circle"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary btn-sm" wire:click="zeigeRechnungenVonLieferant({{ $lieferant->id }})" title="Rechnungen">
                                            <i class="bi bi-list-ul"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm" wire:click="lieferantBearbeiten({{ $lieferant->id }})" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-building fs-1 d-block mb-2"></i>
                                    Noch keine Lieferanten vorhanden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MOBILE: Cards (unter md) --}}
        <div class="d-md-none">
            @forelse($lieferanten as $lieferant)
                <div class="card mb-2 {{ $lieferant->offene_rechnungen_count > 0 ? 'border-warning' : '' }}">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $lieferant->name }}</strong>
                                <br><small class="text-muted">{{ $lieferant->partita_iva }}</small>
                            </div>
                            <div class="text-end">
                                @if($lieferant->offene_rechnungen_count > 0)
                                    <span class="badge bg-warning text-dark">{{ $lieferant->offene_rechnungen_count }} offen</span>
                                    <br><strong class="text-danger">€ {{ number_format($lieferant->summe_offen ?? 0, 2, ',', '.') }}</strong>
                                @else
                                    <span class="text-success"><i class="bi bi-check-circle"></i></span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                            <span>{{ $lieferant->eingangsrechnungen_count }} Rechnungen · € {{ number_format($lieferant->summe_gesamt ?? 0, 2, ',', '.') }}</span>
                            @if(!$lieferant->iban)
                                <span class="text-warning"><i class="bi bi-exclamation-circle"></i> IBAN fehlt</span>
                            @endif
                        </div>
                        
                        <div class="d-flex gap-2 mt-2">
                            <button class="btn btn-outline-primary btn-sm flex-fill" wire:click="zeigeRechnungenVonLieferant({{ $lieferant->id }})">
                                <i class="bi bi-list-ul me-1"></i>Rechnungen
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" wire:click="lieferantBearbeiten({{ $lieferant->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="bi bi-building fs-1 d-block mb-2"></i>
                    Noch keine Lieferanten.
                </div>
            @endforelse
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: IMPORT ERGEBNIS
         ═══════════════════════════════════════════════════════════ --}}
    @if($showImportModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import</h5>
                        <button type="button" class="btn-close" wire:click="importModalSchliessen"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2 mb-3 text-center">
                            <div class="col-4">
                                <div class="p-2 bg-success bg-opacity-25 rounded">
                                    <div class="fs-3 fw-bold text-success">{{ $importErgebnis['erfolg'] ?? 0 }}</div>
                                    <small>Importiert</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 bg-warning bg-opacity-25 rounded">
                                    <div class="fs-3 fw-bold text-warning">{{ $importErgebnis['duplikate'] ?? 0 }}</div>
                                    <small>Duplikate</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 bg-danger bg-opacity-25 rounded">
                                    <div class="fs-3 fw-bold text-danger">{{ $importErgebnis['fehler'] ?? 0 }}</div>
                                    <small>Fehler</small>
                                </div>
                            </div>
                        </div>
                        @if(!empty($importErgebnis['meldungen']))
                            <div class="border rounded" style="max-height: 250px; overflow-y: auto;">
                                @foreach($importErgebnis['meldungen'] as $meldung)
                                    <div class="p-2 border-bottom d-flex align-items-start gap-2 small">
                                        @if($meldung['typ'] === 'success')
                                            <i class="bi bi-check-circle text-success"></i>
                                        @elseif($meldung['typ'] === 'warning')
                                            <i class="bi bi-exclamation-triangle text-warning"></i>
                                        @elseif($meldung['typ'] === 'error')
                                            <i class="bi bi-x-circle text-danger"></i>
                                        @else
                                            <i class="bi bi-info-circle text-info"></i>
                                        @endif
                                        <span>{{ $meldung['text'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-primary w-100" wire:click="importModalSchliessen">OK</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: RECHNUNG BEARBEITEN
         ═══════════════════════════════════════════════════════════ --}}
    @if($bearbeitenId)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Bearbeiten</h5>
                        <button type="button" class="btn-close" wire:click="bearbeitenAbbrechen"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" wire:model.live="bearbeitenStatus">
                                <option value="offen">Offen</option>
                                <option value="bezahlt">Bezahlt</option>
                                <option value="ignoriert">Ignoriert</option>
                            </select>
                        </div>
                        @if($bearbeitenStatus === 'bezahlt')
                            <div class="mb-3">
                                <label class="form-label">Zahlungsmethode</label>
                                <select class="form-select" wire:model="bearbeitenZahlungsmethode">
                                    <option value="">-- Wählen --</option>
                                    <option value="bank">Bank</option>
                                    <option value="karte">Karte</option>
                                    <option value="bar">Bar</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bezahlt am</label>
                                <input type="date" class="form-control" wire:model="bearbeitenBezahltAm">
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Notiz</label>
                            <textarea class="form-control" rows="2" wire:model="bearbeitenNotiz"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" wire:click="bearbeitenAbbrechen">Abbrechen</button>
                        <button type="button" class="btn btn-primary" wire:click="rechnungSpeichern">
                            <i class="bi bi-check-lg me-1"></i>Speichern
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: LIEFERANT BEARBEITEN
         ═══════════════════════════════════════════════════════════ --}}
    @if($lieferantBearbeitenId)
        @php $editLieferant = \App\Models\Lieferant::find($lieferantBearbeitenId); @endphp
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title"><i class="bi bi-building me-2"></i>{{ Str::limit($editLieferant?->name, 20) }}</h5>
                        <button type="button" class="btn-close" wire:click="lieferantBearbeitenAbbrechen"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">IBAN</label>
                            <input type="text" class="form-control font-monospace" wire:model="lieferantIban" placeholder="IT00 0000 0000 0000 0000 0000 000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notiz</label>
                            <textarea class="form-control" rows="2" wire:model="lieferantNotiz"></textarea>
                        </div>
                        <div class="small text-muted">
                            <strong>P.IVA:</strong> {{ $editLieferant?->partita_iva }}<br>
                            <strong>Adresse:</strong> {{ $editLieferant?->adresse_formatiert ?: '-' }}
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" wire:click="lieferantBearbeitenAbbrechen">Abbrechen</button>
                        <button type="button" class="btn btn-primary" wire:click="lieferantSpeichern">
                            <i class="bi bi-check-lg me-1"></i>Speichern
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: RECHNUNG DETAIL
         ═══════════════════════════════════════════════════════════ --}}
    @if($detailRechnung)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title">
                            <i class="bi bi-receipt me-2"></i>{{ $detailRechnung->rechnungsnummer }}
                            @if($detailRechnung->istGutschrift())
                                <span class="badge bg-info ms-2">Gutschrift</span>
                            @endif
                        </h5>
                        <button type="button" class="btn-close" wire:click="detailSchliessen"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Kopfdaten --}}
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body py-2 px-3">
                                        <strong>{{ $detailRechnung->lieferant->name }}</strong><br>
                                        <small class="text-muted">
                                            P.IVA: {{ $detailRechnung->lieferant->partita_iva }}
                                        </small>
                                        @if($detailRechnung->lieferant->iban)
                                            <br><code class="small">{{ $detailRechnung->lieferant->iban_formatiert }}</code>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex justify-content-between small">
                                            <span class="text-muted">Datum:</span>
                                            <span>{{ $detailRechnung->rechnungsdatum->format('d.m.Y') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between small">
                                            <span class="text-muted">Fälligkeit:</span>
                                            <span>{{ $detailRechnung->faelligkeitsdatum?->format('d.m.Y') ?? '-' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between small">
                                            <span class="text-muted">Status:</span>
                                            <span class="badge {{ $detailRechnung->status_badge_class }}">{{ ucfirst($detailRechnung->status) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Artikel --}}
                        <h6><i class="bi bi-list-ul me-2"></i>Positionen</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped small">
                                <thead>
                                    <tr>
                                        <th>Beschreibung</th>
                                        <th class="text-end">Menge</th>
                                        <th class="text-end">Preis</th>
                                        <th class="text-end">Gesamt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($detailRechnung->artikel as $artikel)
                                        <tr>
                                            <td>{{ Str::limit($artikel->beschreibung, 30) }}</td>
                                            <td class="text-end text-nowrap">{{ number_format($artikel->menge, 2, ',', '.') }} {{ $artikel->einheit }}</td>
                                            <td class="text-end text-nowrap">€ {{ number_format($artikel->einzelpreis, 2, ',', '.') }}</td>
                                            <td class="text-end text-nowrap">€ {{ number_format($artikel->gesamtpreis, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Netto:</strong></td>
                                        <td class="text-end">€ {{ number_format($detailRechnung->netto_betrag, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>MwSt:</strong></td>
                                        <td class="text-end">€ {{ number_format($detailRechnung->mwst_betrag, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Brutto:</strong></td>
                                        <td class="text-end fw-bold">€ {{ number_format($detailRechnung->brutto_betrag, 2, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary w-100" wire:click="detailSchliessen">Schließen</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
