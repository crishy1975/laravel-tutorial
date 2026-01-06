{{--
════════════════════════════════════════════════════════════════════════════
DATEI: lohnstunden-uebersicht.blade.php
PFAD:  resources/views/livewire/admin/lohnstunden-uebersicht.blade.php
════════════════════════════════════════════════════════════════════════════
--}}
<div>
    {{-- Styles --}}
    <style>
        .sticky-col {
            position: sticky;
            left: 0;
            z-index: 1;
        }
        
        .lohnstunden-table-wrapper {
            max-height: 70vh;
            overflow: auto;
        }
        
        .lohnstunden-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }
        
        .lohnstunden-table thead th.sticky-col {
            z-index: 3;
        }

        /* Mobile Card Styles */
        .mobile-card {
            border-left: 4px solid #0d6efd;
            transition: all 0.2s;
        }
        
        .mobile-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .mobile-card .badge {
            font-size: 0.85rem;
        }
        
        .tages-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        
        .tages-grid .tag-cell {
            text-align: center;
            padding: 4px;
            border-radius: 4px;
            font-size: 0.75rem;
            min-height: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .tages-grid .tag-cell.weekend {
            background-color: #e9ecef;
        }
        
        .tages-grid .tag-cell.has-entry {
            background-color: #d1e7dd;
            cursor: pointer;
        }
        
        .tages-grid .tag-cell.empty {
            background-color: #f8f9fa;
        }

        /* Responsive visibility */
        @media (max-width: 991.98px) {
            .desktop-only {
                display: none !important;
            }
        }
        
        @media (min-width: 992px) {
            .mobile-only {
                display: none !important;
            }
        }
    </style>

    {{-- Header --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 mt-4">
        <h2 class="mb-0 fs-4 fs-md-2">
            <i class="bi bi-clock-history text-secondary"></i> 
            <span class="d-none d-sm-inline">Lohnstunden-Übersicht</span>
            <span class="d-sm-none">Lohnstunden</span>
        </h2>
        <div class="d-flex gap-2 flex-wrap">
            <button wire:click="exportExcel" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> 
                <span class="d-none d-sm-inline">Excel Export</span>
                <span class="d-sm-none">Export</span>
            </button>
            <button wire:click="openEmailModal" class="btn btn-primary btn-sm">
                <i class="bi bi-envelope"></i> 
                <span class="d-none d-sm-inline">Per E-Mail</span>
            </button>
        </div>
    </div>

    {{-- Erfolgs-/Fehlermeldungen --}}
    @if($successMessage)
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-check-circle"></i> {{ $successMessage }}
            <button type="button" class="btn-close" wire:click="$set('successMessage', '')"></button>
        </div>
    @endif

    @if($errorMessage)
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ $errorMessage }}
            <button type="button" class="btn-close" wire:click="$set('errorMessage', '')"></button>
        </div>
    @endif

    {{-- Filter (Mobile-optimiert) --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-2 g-md-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold small mb-1">
                        <i class="bi bi-person"></i> Mitarbeiter
                    </label>
                    <select wire:model.live="selectedMitarbeiter" class="form-select form-select-sm">
                        <option value="">Alle Mitarbeiter</option>
                        @foreach($this->mitarbeiter as $ma)
                            <option value="{{ $ma->id }}">{{ $ma->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold small mb-1">
                        <i class="bi bi-calendar-month"></i> Monat
                    </label>
                    <select wire:model.live="selectedMonat" class="form-select form-select-sm">
                        @foreach($monate as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold small mb-1">
                        <i class="bi bi-calendar"></i> Jahr
                    </label>
                    <select wire:model.live="selectedJahr" class="form-select form-select-sm">
                        @foreach($jahre as $jahr)
                            <option value="{{ $jahr }}">{{ $jahr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <div class="text-muted small">
                        <i class="bi bi-info-circle"></i> 
                        {{ count($this->monatsUebersicht) }} Mitarbeiter, 
                        {{ $this->lohnstunden->count() }} Einträge
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         DESKTOP: Monatsübersicht Tabelle (Tage als Spalten)
         ═══════════════════════════════════════════════════════════════ --}}
    <div class="card mb-4 desktop-only">
        <div class="card-header bg-dark text-white py-2">
            <i class="bi bi-table"></i> 
            Monatsübersicht: {{ $monate[$selectedMonat] }} {{ $selectedJahr }}
        </div>
        <div class="card-body p-0">
            <div class="lohnstunden-table-wrapper">
                <table class="table table-sm table-bordered table-hover mb-0 lohnstunden-table" style="font-size: 0.8rem;">
                    <thead class="table-dark">
                        <tr>
                            <th class="sticky-col bg-dark text-white" style="min-width: 140px;">Mitarbeiter</th>
                            @for($tag = 1; $tag <= $anzahlTage; $tag++)
                                @php
                                    $datum = \Carbon\Carbon::create($selectedJahr, $selectedMonat, $tag);
                                    $isWeekend = $datum->isWeekend();
                                @endphp
                                <th class="text-center {{ $isWeekend ? 'bg-secondary' : '' }}" 
                                    style="min-width: 38px; {{ $isWeekend ? 'opacity: 0.8;' : '' }}">
                                    <div>{{ $tag }}</div>
                                    <small class="text-muted" style="font-size: 0.65rem;">{{ $datum->shortDayName }}</small>
                                </th>
                            @endfor
                            @foreach(['No', 'Üb', 'F', 'P', 'K', 'U'] as $typ)
                                <th class="text-center bg-light" style="min-width: 35px;">{{ $typ }}</th>
                            @endforeach
                            <th class="text-center bg-warning" style="min-width: 45px;">Σ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->monatsUebersicht as $data)
                            <tr>
                                <td class="sticky-col bg-white fw-semibold text-truncate" style="max-width: 140px;">
                                    {{ $data['user']->name }}
                                </td>
                                @for($tag = 1; $tag <= $anzahlTage; $tag++)
                                    @php
                                        $datum = \Carbon\Carbon::create($selectedJahr, $selectedMonat, $tag);
                                        $isWeekend = $datum->isWeekend();
                                        $tagesEintraege = $data['tage'][$tag] ?? collect();
                                    @endphp
                                    <td class="text-center p-0 {{ $isWeekend ? 'bg-light' : '' }}">
                                        @if($tagesEintraege->count() > 0)
                                            @foreach($tagesEintraege as $eintrag)
                                                <span class="badge {{ $this->getTypBadgeClass($eintrag->typ) }} rounded-0 w-100" 
                                                      style="font-size: 0.65rem; cursor: pointer; padding: 3px 2px;"
                                                      wire:click="editEintrag({{ $eintrag->id }})"
                                                      title="{{ $eintrag->typ }}: {{ number_format($eintrag->stunden, 1) }}h">
                                                    {{ $eintrag->typ }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-muted" style="font-size: 0.7rem;">-</span>
                                        @endif
                                    </td>
                                @endfor
                                @foreach(['No', 'Üb', 'F', 'P', 'K', 'U'] as $typ)
                                    <td class="text-center bg-light" style="font-size: 0.75rem;">
                                        @if(($data['summen'][$typ] ?? 0) > 0)
                                            <strong>{{ number_format($data['summen'][$typ], 0) }}</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center bg-warning fw-bold" style="font-size: 0.75rem;">
                                    {{ number_format($data['total'], 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $anzahlTage + 8 }}" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Keine Daten vorhanden
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         MOBILE: Karten-Ansicht pro Mitarbeiter
         ═══════════════════════════════════════════════════════════════ --}}
    <div class="mobile-only mb-4">
        @forelse($this->monatsUebersicht as $data)
            <div class="card mobile-card mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <strong>
                        <i class="bi bi-person-circle text-muted"></i>
                        {{ $data['user']->name }}
                    </strong>
                    <span class="badge bg-warning text-dark">
                        Σ {{ number_format($data['total'], 1) }}h
                    </span>
                </div>
                <div class="card-body py-2">
                    {{-- Summen-Badges --}}
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        @foreach(['No' => 'primary', 'Üb' => 'warning', 'F' => 'success', 'P' => 'info', 'K' => 'danger', 'U' => 'danger'] as $typ => $color)
                            @if(($data['summen'][$typ] ?? 0) > 0)
                                <span class="badge bg-{{ $color }} {{ $color === 'warning' ? 'text-dark' : '' }}">
                                    {{ $typ }}: {{ number_format($data['summen'][$typ], 1) }}h
                                </span>
                            @endif
                        @endforeach
                    </div>
                    
                    {{-- Tage-Grid (7 Tage pro Zeile wie Kalender) --}}
                    <div class="tages-grid">
                        @for($tag = 1; $tag <= $anzahlTage; $tag++)
                            @php
                                $datum = \Carbon\Carbon::create($selectedJahr, $selectedMonat, $tag);
                                $isWeekend = $datum->isWeekend();
                                $tagesEintraege = $data['tage'][$tag] ?? collect();
                                $hasEntry = $tagesEintraege->count() > 0;
                            @endphp
                            <div class="tag-cell {{ $isWeekend ? 'weekend' : '' }} {{ $hasEntry ? 'has-entry' : 'empty' }}"
                                 @if($hasEntry && $tagesEintraege->first())
                                     wire:click="editEintrag({{ $tagesEintraege->first()->id }})"
                                 @endif>
                                <small class="text-muted">{{ $tag }}</small>
                                @if($hasEntry)
                                    @foreach($tagesEintraege as $eintrag)
                                        <span class="badge {{ $this->getTypBadgeClass($eintrag->typ) }}" style="font-size: 0.6rem; padding: 1px 3px;">
                                            {{ $eintrag->typ }}
                                        </span>
                                    @endforeach
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Keine Daten für diesen Zeitraum
            </div>
        @endforelse
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         Detail-Tabelle (alle Einträge) - Responsive Cards auf Mobile
         ═══════════════════════════════════════════════════════════════ --}}
    <div class="card">
        <div class="card-header py-2">
            <i class="bi bi-list-ul"></i> Alle Einträge
        </div>
        
        {{-- Desktop: Tabelle --}}
        <div class="card-body p-0 desktop-only">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mitarbeiter</th>
                            <th>Datum</th>
                            <th>Typ</th>
                            <th>Stunden</th>
                            <th>Notizen</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->lohnstunden as $eintrag)
                            <tr>
                                <td>{{ $eintrag->user->name }}</td>
                                <td>
                                    {{ $eintrag->datum->format('d.m.Y') }}
                                    <small class="text-muted">({{ $eintrag->datum->shortDayName }})</small>
                                </td>
                                <td>
                                    <span class="badge {{ $this->getTypBadgeClass($eintrag->typ) }}">
                                        {{ $eintrag->typ }}
                                    </span>
                                </td>
                                <td><strong>{{ number_format($eintrag->stunden, 2) }}</strong>h</td>
                                <td>
                                    @if($eintrag->notizen)
                                        <small class="text-muted">{{ Str::limit($eintrag->notizen, 30) }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button wire:click="editEintrag({{ $eintrag->id }})" class="btn btn-sm btn-outline-primary py-0 px-1">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button wire:click="deleteEintrag({{ $eintrag->id }})" wire:confirm="Löschen?" class="btn btn-sm btn-outline-danger py-0 px-1">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Keine Einträge</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Mobile: Karten --}}
        <div class="card-body p-2 mobile-only">
            @forelse($this->lohnstunden as $eintrag)
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge {{ $this->getTypBadgeClass($eintrag->typ) }}">{{ $eintrag->typ }}</span>
                            <strong>{{ number_format($eintrag->stunden, 1) }}h</strong>
                            <small class="text-muted">{{ $eintrag->datum->format('d.m.') }}</small>
                        </div>
                        <small class="text-muted">{{ $eintrag->user->name }}</small>
                        @if($eintrag->notizen)
                            <br><small class="text-muted fst-italic">{{ Str::limit($eintrag->notizen, 40) }}</small>
                        @endif
                    </div>
                    <div class="d-flex gap-1">
                        <button wire:click="editEintrag({{ $eintrag->id }})" class="btn btn-sm btn-outline-primary py-1 px-2">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button wire:click="deleteEintrag({{ $eintrag->id }})" wire:confirm="Löschen?" class="btn btn-sm btn-outline-danger py-1 px-2">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">Keine Einträge</div>
            @endforelse
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         MODALS (funktionieren auf allen Geräten)
         ═══════════════════════════════════════════════════════════════ --}}

    {{-- Edit Modal --}}
    @if($showEditModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h6 class="modal-title">
                            <i class="bi bi-pencil"></i> Eintrag bearbeiten
                        </h6>
                        <button type="button" class="btn-close" wire:click="$set('showEditModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small">Datum</label>
                                <input type="date" wire:model="editDatum" class="form-control form-control-sm">
                                @error('editDatum') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Stunden</label>
                                <input type="number" wire:model="editStunden" class="form-control form-control-sm" step="0.25" min="0" max="24">
                                @error('editStunden') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Typ</label>
                                <select wire:model="editTyp" class="form-select form-select-sm">
                                    @foreach($typen as $code => $label)
                                        <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('editTyp') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Notizen</label>
                                <textarea wire:model="editNotizen" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showEditModal', false)">
                            Abbrechen
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="saveEintrag">
                            <i class="bi bi-check"></i> Speichern
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- E-Mail Modal --}}
    @if($showEmailModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h6 class="modal-title">
                            <i class="bi bi-envelope"></i> Per E-Mail senden
                        </h6>
                        <button type="button" class="btn-close" wire:click="$set('showEmailModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small">
                            <i class="bi bi-info-circle"></i>
                            Excel für <strong>{{ $monate[$selectedMonat] }} {{ $selectedJahr }}</strong> als Anhang.
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">E-Mail-Adresse *</label>
                            <input type="email" wire:model="emailAdresse" class="form-control form-control-sm" placeholder="empfaenger@example.com">
                            @error('emailAdresse') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Betreff *</label>
                            <input type="text" wire:model="emailBetreff" class="form-control form-control-sm">
                            @error('emailBetreff') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-0">
                            <label class="form-label small">Nachricht</label>
                            <textarea wire:model="emailNachricht" class="form-control form-control-sm" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showEmailModal', false)">
                            Abbrechen
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="sendEmail">
                            <i class="bi bi-send"></i> Senden
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
