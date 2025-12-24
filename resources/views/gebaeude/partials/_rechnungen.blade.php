{{-- resources/views/gebaeude/partials/_rechnungen.blade.php --}}
{{-- MOBIL-OPTIMIERT: Card-Layout auf Smartphones --}}

@if(!isset($gebaeude) || !$gebaeude->id)
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Rechnungen erst nach Erstellen des Gebaeudes verfuegbar.
    </div>
@else
    @php
        $rechnungen = $gebaeude->rechnungen()->orderByDesc('rechnungsdatum')->orderByDesc('id')->get();
        $statusConfig = [
            'draft' => ['class' => 'secondary', 'icon' => 'pencil-square', 'label' => 'Entwurf'],
            'sent' => ['class' => 'info', 'icon' => 'send', 'label' => 'Versendet'],
            'paid' => ['class' => 'success', 'icon' => 'check-circle-fill', 'label' => 'Bezahlt'],
            'cancelled' => ['class' => 'danger', 'icon' => 'x-circle', 'label' => 'Storniert'],
            'overdue' => ['class' => 'warning', 'icon' => 'exclamation-triangle', 'label' => 'Ueberfaellig'],
        ];
    @endphp

    <div class="row g-3">
        {{-- Header --}}
        <div class="col-12">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <div>
                    <h6 class="mb-0">
                        <i class="bi bi-receipt"></i> Rechnungen
                    </h6>
                    <small class="text-muted">{{ $rechnungen->count() }} Rechnungen</small>
                </div>
                <a href="{{ route('rechnung.create', ['gebaeude_id' => $gebaeude->id]) }}" 
                   class="btn btn-success btn-sm">
                    <i class="bi bi-plus-circle"></i> Neue Rechnung
                </a>
            </div>
        </div>

        @if($rechnungen->isEmpty())
            <div class="col-12">
                <div class="alert alert-info d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                    <div>
                        <i class="bi bi-info-circle"></i> Noch keine Rechnungen erstellt.
                    </div>
                    <a href="{{ route('rechnung.create', ['gebaeude_id' => $gebaeude->id]) }}" 
                       class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Erste Rechnung
                    </a>
                </div>
            </div>
        @else
            {{-- MOBILE: Cards --}}
            <div class="col-12 d-md-none">
                @foreach($rechnungen as $rechnung)
                    @php $config = $statusConfig[$rechnung->status] ?? ['class' => 'secondary', 'icon' => 'question', 'label' => $rechnung->status]; @endphp
                    <div class="card mb-2">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong>{{ $rechnung->rechnungsnummer ?? '-' }}</strong>
                                    <div class="text-muted small">{{ $rechnung->rechnungsdatum?->format('d.m.Y') }}</div>
                                </div>
                                <span class="badge bg-{{ $config['class'] }}">
                                    <i class="bi bi-{{ $config['icon'] }}"></i> {{ $config['label'] }}
                                </span>
                            </div>
                            <div class="small text-muted mb-2">{{ Str::limit($rechnung->re_name ?? '-', 30) }}</div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold">{{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }} EUR</span>
                                    <span class="text-muted small">(netto: {{ number_format($rechnung->netto_summe ?? 0, 2, ',', '.') }})</span>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('rechnung.edit', $rechnung->id) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($rechnung->status !== 'draft')
                                    <a href="{{ route('rechnung.pdf', $rechnung->id) }}" class="btn btn-outline-secondary" target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- DESKTOP: Tabelle --}}
            <div class="col-12 d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Nr.</th>
                                <th>Datum</th>
                                <th>Status</th>
                                <th>Empfaenger</th>
                                <th class="text-end">Netto</th>
                                <th class="text-end">Brutto</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rechnungen as $rechnung)
                                @php $config = $statusConfig[$rechnung->status] ?? ['class' => 'secondary', 'icon' => 'question', 'label' => $rechnung->status]; @endphp
                                <tr>
                                    <td><strong>{{ $rechnung->rechnungsnummer ?? '-' }}</strong></td>
                                    <td>{{ $rechnung->rechnungsdatum?->format('d.m.Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $config['class'] }}">
                                            <i class="bi bi-{{ $config['icon'] }}"></i> {{ $config['label'] }}
                                        </span>
                                    </td>
                                    <td>{{ Str::limit($rechnung->re_name ?? '-', 25) }}</td>
                                    <td class="text-end">{{ number_format($rechnung->netto_summe ?? 0, 2, ',', '.') }}</td>
                                    <td class="text-end"><strong>{{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }}</strong></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('rechnung.edit', $rechnung->id) }}" class="btn btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @if($rechnung->status !== 'draft')
                                            <a href="{{ route('rechnung.pdf', $rechnung->id) }}" class="btn btn-outline-secondary" target="_blank">
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
                                <td colspan="4">Gesamt</td>
                                <td class="text-end">{{ number_format($rechnungen->sum('netto_summe'), 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($rechnungen->sum('brutto_summe'), 2, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Statistik-Karten - responsive --}}
            <div class="col-12">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <div class="card border-secondary h-100">
                            <div class="card-body text-center p-2">
                                <i class="bi bi-pencil-square text-secondary"></i>
                                <div class="small text-muted">Entwuerfe</div>
                                <strong>{{ $rechnungen->where('status', 'draft')->count() }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-info h-100">
                            <div class="card-body text-center p-2">
                                <i class="bi bi-send text-info"></i>
                                <div class="small text-muted">Versendet</div>
                                <strong>{{ $rechnungen->where('status', 'sent')->count() }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-success h-100">
                            <div class="card-body text-center p-2">
                                <i class="bi bi-check-circle text-success"></i>
                                <div class="small text-muted">Bezahlt</div>
                                <strong class="text-success">{{ $rechnungen->where('status', 'paid')->count() }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-warning h-100">
                            <div class="card-body text-center p-2">
                                <i class="bi bi-exclamation-triangle text-warning"></i>
                                <div class="small text-muted">Offen</div>
                                <strong class="text-warning">{{ $rechnungen->whereIn('status', ['sent', 'overdue'])->count() }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Umsatz kompakt --}}
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-body p-2">
                        <div class="row text-center">
                            <div class="col-4 border-end">
                                <small class="text-muted d-block">Gesamt</small>
                                <strong>{{ number_format($rechnungen->sum('brutto_summe'), 2, ',', '.') }}</strong>
                            </div>
                            <div class="col-4 border-end">
                                <small class="text-muted d-block">Bezahlt</small>
                                <strong class="text-success">{{ number_format($rechnungen->where('status', 'paid')->sum('brutto_summe'), 2, ',', '.') }}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Offen</small>
                                <strong class="text-warning">{{ number_format($rechnungen->whereIn('status', ['sent', 'overdue'])->sum('brutto_summe'), 2, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
