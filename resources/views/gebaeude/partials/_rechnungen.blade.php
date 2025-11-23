{{-- Tab: Rechnungen zu diesem Gebäude --}}
{{-- Dieser Tab ist READ-ONLY (keine Form-Felder) und zeigt nur die Liste --}}

@if(!isset($gebaeude) || !$gebaeude->id)
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Rechnungen können erst nach dem Erstellen des Gebäudes angezeigt werden.
    </div>
@else
    <div class="row g-3">
        {{-- Header mit Button --}}
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">
                        <i class="bi bi-receipt"></i>
                        Rechnungen zu diesem Gebäude
                    </h5>
                    <small class="text-muted">
                        Gesamt: {{ $gebaeude->rechnungen->count() }} Rechnungen
                    </small>
                </div>
                <a href="{{ route('gebaeude.rechnung.create', $gebaeude->id) }}" 
                   class="btn btn-success">
                    <i class="bi bi-plus-lg"></i> Neue Rechnung
                </a>
            </div>
        </div>

        @if($gebaeude->rechnungen->isEmpty())
            {{-- Keine Rechnungen vorhanden --}}
            <div class="col-12">
                <div class="alert alert-info d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-info-circle"></i>
                        Noch keine Rechnungen für dieses Gebäude erstellt.
                    </div>
                    <a href="{{ route('gebaeude.rechnung.create', $gebaeude->id) }}" 
                       class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Erste Rechnung erstellen
                    </a>
                </div>
            </div>
        @else
            {{-- Rechnungsliste --}}
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Rechnung-Nr.</th>
                                <th>Datum</th>
                                <th>Status</th>
                                <th>Empfänger</th>
                                <th class="text-end">Netto</th>
                                <th class="text-end">MwSt.</th>
                                <th class="text-end">Brutto</th>
                                <th class="text-center">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gebaeude->rechnungen()->orderByDesc('rechnungsdatum')->orderByDesc('id')->get() as $rechnung)
                                <tr>
                                    <td><strong>{{ $rechnung->rechnungsnummer ?? '-' }}</strong></td>
                                    <td>{{ $rechnung->rechnungsdatum ? $rechnung->rechnungsdatum->format('d.m.Y') : '-' }}</td>
                                    <td>
                                        @php
                                            $statusConfig = [
                                                'draft' => ['class' => 'secondary', 'icon' => 'pencil-square', 'label' => 'Entwurf'],
                                                'sent' => ['class' => 'info', 'icon' => 'send', 'label' => 'Versendet'],
                                                'paid' => ['class' => 'success', 'icon' => 'check-circle-fill', 'label' => 'Bezahlt'],
                                                'cancelled' => ['class' => 'danger', 'icon' => 'x-circle', 'label' => 'Storniert'],
                                                'overdue' => ['class' => 'warning', 'icon' => 'exclamation-triangle', 'label' => 'Überfällig'],
                                            ];
                                            $config = $statusConfig[$rechnung->status] ?? ['class' => 'secondary', 'icon' => 'question-circle', 'label' => $rechnung->status];
                                        @endphp
                                        <span class="badge bg-{{ $config['class'] }}">
                                            <i class="bi bi-{{ $config['icon'] }}"></i>
                                            {{ $config['label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>{{ Str::limit($rechnung->re_name ?? '-', 35) }}</div>
                                        @if($rechnung->re_wohnort)
                                            <small class="text-muted">{{ $rechnung->re_wohnort }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($rechnung->netto_summe ?? 0, 2, ',', '.') }} €</td>
                                    <td class="text-end">{{ number_format($rechnung->mwst_betrag ?? 0, 2, ',', '.') }} €</td>
                                    <td class="text-end">
                                        <strong>{{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }} €</strong>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('rechnung.edit', $rechnung->id) }}" 
                                               class="btn btn-outline-primary" 
                                               title="Bearbeiten">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @if($rechnung->status !== 'draft')
                                                <a href="{{ route('rechnung.pdf', $rechnung->id) }}" 
                                                   class="btn btn-outline-secondary" 
                                                   target="_blank"
                                                   title="PDF öffnen">
                                                    <i class="bi bi-file-pdf"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="4">Gesamt ({{ $gebaeude->rechnungen->count() }} Rechnungen)</td>
                                <td class="text-end">{{ number_format($gebaeude->rechnungen->sum('netto_summe'), 2, ',', '.') }} €</td>
                                <td class="text-end">{{ number_format($gebaeude->rechnungen->sum('mwst_betrag'), 2, ',', '.') }} €</td>
                                <td class="text-end">{{ number_format($gebaeude->rechnungen->sum('brutto_summe'), 2, ',', '.') }} €</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-success">
                                    <i class="bi bi-check-circle"></i> Davon bezahlt
                                </td>
                                <td class="text-end text-success">
                                    {{ number_format($gebaeude->rechnungen->where('status', 'paid')->sum('netto_summe'), 2, ',', '.') }} €
                                </td>
                                <td class="text-end text-success">
                                    {{ number_format($gebaeude->rechnungen->where('status', 'paid')->sum('mwst_betrag'), 2, ',', '.') }} €
                                </td>
                                <td class="text-end text-success">
                                    {{ number_format($gebaeude->rechnungen->where('status', 'paid')->sum('brutto_summe'), 2, ',', '.') }} €
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-warning">
                                    <i class="bi bi-exclamation-triangle"></i> Davon offen
                                </td>
                                <td class="text-end text-warning">
                                    {{ number_format($gebaeude->rechnungen->whereIn('status', ['sent', 'overdue'])->sum('netto_summe'), 2, ',', '.') }} €
                                </td>
                                <td class="text-end text-warning">
                                    {{ number_format($gebaeude->rechnungen->whereIn('status', ['sent', 'overdue'])->sum('mwst_betrag'), 2, ',', '.') }} €
                                </td>
                                <td class="text-end text-warning">
                                    {{ number_format($gebaeude->rechnungen->whereIn('status', ['sent', 'overdue'])->sum('brutto_summe'), 2, ',', '.') }} €
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Statistik-Karten --}}
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card border-secondary h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-pencil-square fs-2 text-secondary"></i>
                                <h6 class="text-muted mt-2 mb-1">Entwürfe</h6>
                                <h3 class="mb-0">{{ $gebaeude->rechnungen->where('status', 'draft')->count() }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-info h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-send fs-2 text-info"></i>
                                <h6 class="text-muted mt-2 mb-1">Versendet</h6>
                                <h3 class="mb-0">{{ $gebaeude->rechnungen->where('status', 'sent')->count() }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-success h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                                <h6 class="text-muted mt-2 mb-1">Bezahlt</h6>
                                <h3 class="mb-0 text-success">{{ $gebaeude->rechnungen->where('status', 'paid')->count() }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-warning h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-exclamation-triangle fs-2 text-warning"></i>
                                <h6 class="text-muted mt-2 mb-1">Überfällig</h6>
                                <h3 class="mb-0 text-warning">{{ $gebaeude->rechnungen->where('status', 'overdue')->count() }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Umsatz-Übersicht --}}
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-graph-up"></i> Umsatz-Übersicht
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="border-end">
                                    <small class="text-muted d-block mb-1">Gesamt-Umsatz</small>
                                    <h4 class="mb-0">{{ number_format($gebaeude->rechnungen->sum('brutto_summe'), 2, ',', '.') }} €</h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border-end">
                                    <small class="text-muted d-block mb-1">Bezahlt</small>
                                    <h4 class="mb-0 text-success">
                                        {{ number_format($gebaeude->rechnungen->where('status', 'paid')->sum('brutto_summe'), 2, ',', '.') }} €
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block mb-1">Offen</small>
                                <h4 class="mb-0 text-warning">
                                    {{ number_format($gebaeude->rechnungen->whereIn('status', ['sent', 'overdue'])->sum('brutto_summe'), 2, ',', '.') }} €
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif