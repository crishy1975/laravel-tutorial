{{-- resources/views/gebaeude/partials/_rechnungen.blade.php --}}
{{-- MOBIL-OPTIMIERT: Card-Layout auf Smartphones --}}
{{-- ⭐ NEU: Gutschriften werden rot und mit negativem Betrag angezeigt --}}

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

        // ⭐ Separate Zählung für Statistiken
        $nurRechnungen = $rechnungen->where('typ_rechnung', '!=', 'gutschrift');
        $nurGutschriften = $rechnungen->where('typ_rechnung', 'gutschrift');
        
        // ⭐ Effektiver Umsatz (Rechnungen minus Gutschriften)
        $bruttoRechnungen = $nurRechnungen->sum('brutto_summe');
        $bruttoGutschriften = $nurGutschriften->sum('brutto_summe');
        $effektivBrutto = $bruttoRechnungen - $bruttoGutschriften;
        
        $nettoRechnungen = $nurRechnungen->sum('netto_summe');
        $nettoGutschriften = $nurGutschriften->sum('netto_summe');
        $effektivNetto = $nettoRechnungen - $nettoGutschriften;
    @endphp

    <div class="row g-3">
        {{-- Header --}}
        <div class="col-12">
            <div>
                <h6 class="mb-0">
                    <i class="bi bi-receipt"></i> Rechnungen
                </h6>
                <small class="text-muted">
                    {{ $nurRechnungen->count() }} Rechnungen
                    @if($nurGutschriften->count() > 0)
                        <span class="text-danger">/ {{ $nurGutschriften->count() }} Gutschriften</span>
                    @endif
                </small>
            </div>
        </div>

        @if($rechnungen->isEmpty())
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Noch keine Rechnungen erstellt.
                </div>
            </div>
        @else
            {{-- MOBILE: Cards --}}
            <div class="col-12 d-md-none">
                @foreach($rechnungen as $rechnung)
                    @php 
                        $config = $statusConfig[$rechnung->status] ?? ['class' => 'secondary', 'icon' => 'question', 'label' => $rechnung->status];
                        $istGutschrift = $rechnung->typ_rechnung === 'gutschrift';
                    @endphp
                    <div class="card mb-2 {{ $istGutschrift ? 'border-danger' : '' }}">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong class="{{ $istGutschrift ? 'text-danger' : '' }}">
                                        {{ $rechnung->rechnungsnummer ?? '-' }}
                                    </strong>
                                    {{-- ⭐ Typ-Badge --}}
                                    @if($istGutschrift)
                                        <span class="badge bg-danger ms-1">Gutschrift</span>
                                    @endif
                                    <div class="text-muted small">{{ $rechnung->rechnungsdatum?->format('d.m.Y') }}</div>
                                </div>
                                <span class="badge bg-{{ $config['class'] }}">
                                    <i class="bi bi-{{ $config['icon'] }}"></i> {{ $config['label'] }}
                                </span>
                            </div>
                            <div class="small text-muted mb-2">{{ Str::limit($rechnung->re_name ?? '-', 30) }}</div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    {{-- ⭐ Gutschrift: Negativer Betrag in Rot --}}
                                    <span class="fw-bold {{ $istGutschrift ? 'text-danger' : '' }}">
                                        {{ $istGutschrift ? '-' : '' }}{{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }} EUR
                                    </span>
                                    <span class="text-muted small">
                                        (netto: {{ $istGutschrift ? '-' : '' }}{{ number_format($rechnung->netto_summe ?? 0, 2, ',', '.') }})
                                    </span>
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
                                <th>Typ</th>
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
                                @php 
                                    $config = $statusConfig[$rechnung->status] ?? ['class' => 'secondary', 'icon' => 'question', 'label' => $rechnung->status];
                                    $istGutschrift = $rechnung->typ_rechnung === 'gutschrift';
                                @endphp
                                <tr class="{{ $istGutschrift ? 'table-danger' : '' }}">
                                    <td>
                                        <strong class="{{ $istGutschrift ? 'text-danger' : '' }}">
                                            {{ $rechnung->rechnungsnummer ?? '-' }}
                                        </strong>
                                    </td>
                                    <td>
                                        {{-- ⭐ Typ-Badge --}}
                                        @if($istGutschrift)
                                            <span class="badge bg-danger">
                                                <i class="bi bi-dash-circle"></i> Gutschrift
                                            </span>
                                        @else
                                            <span class="badge bg-primary">
                                                <i class="bi bi-receipt"></i> Rechnung
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $rechnung->rechnungsdatum?->format('d.m.Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $config['class'] }}">
                                            <i class="bi bi-{{ $config['icon'] }}"></i> {{ $config['label'] }}
                                        </span>
                                    </td>
                                    <td>{{ Str::limit($rechnung->re_name ?? '-', 25) }}</td>
                                    {{-- ⭐ Gutschrift: Negativer Betrag in Rot --}}
                                    <td class="text-end {{ $istGutschrift ? 'text-danger fw-bold' : '' }}">
                                        {{ $istGutschrift ? '-' : '' }}{{ number_format($rechnung->netto_summe ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end {{ $istGutschrift ? 'text-danger fw-bold' : '' }}">
                                        <strong>{{ $istGutschrift ? '-' : '' }}{{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }}</strong>
                                    </td>
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
                            {{-- ⭐ Separierte Summen --}}
                            @if($nurGutschriften->count() > 0)
                            <tr>
                                <td colspan="5" class="text-end">Summe Rechnungen</td>
                                <td class="text-end">{{ number_format($nettoRechnungen, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($bruttoRechnungen, 2, ',', '.') }}</td>
                                <td></td>
                            </tr>
                            <tr class="text-danger">
                                <td colspan="5" class="text-end">Summe Gutschriften</td>
                                <td class="text-end">-{{ number_format($nettoGutschriften, 2, ',', '.') }}</td>
                                <td class="text-end">-{{ number_format($bruttoGutschriften, 2, ',', '.') }}</td>
                                <td></td>
                            </tr>
                            @endif
                            <tr class="fw-bold">
                                <td colspan="5">Effektiv Gesamt</td>
                                <td class="text-end">{{ number_format($effektivNetto, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($effektivBrutto, 2, ',', '.') }}</td>
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
                    {{-- ⭐ NEU: Gutschriften-Karte --}}
                    <div class="col-6 col-md-3">
                        <div class="card border-danger h-100">
                            <div class="card-body text-center p-2">
                                <i class="bi bi-dash-circle text-danger"></i>
                                <div class="small text-muted">Gutschriften</div>
                                <strong class="text-danger">{{ $nurGutschriften->count() }}</strong>
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
                            <div class="col-3 border-end">
                                <small class="text-muted d-block">Rechnungen</small>
                                <strong>{{ number_format($bruttoRechnungen, 2, ',', '.') }}</strong>
                            </div>
                            <div class="col-3 border-end">
                                <small class="text-muted d-block">Gutschriften</small>
                                <strong class="text-danger">-{{ number_format($bruttoGutschriften, 2, ',', '.') }}</strong>
                            </div>
                            <div class="col-3 border-end">
                                <small class="text-muted d-block">Effektiv</small>
                                <strong class="{{ $effektivBrutto < 0 ? 'text-danger' : 'text-primary' }}">
                                    {{ number_format($effektivBrutto, 2, ',', '.') }}
                                </strong>
                            </div>
                            <div class="col-3">
                                <small class="text-muted d-block">Offen</small>
                                <strong class="text-warning">
                                    {{ number_format($nurRechnungen->whereIn('status', ['sent', 'overdue'])->sum('brutto_summe'), 2, ',', '.') }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
