<div>
    <h2 class="h4 mb-4">
        <i class="bi bi-house-door text-success"></i>
        Hallo / Ciao, {{ auth()->user()->name }}!
    </h2>

    {{-- Statistik-Karten / Statistiche --}}
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="card stat-card bg-white h-100">
                <div class="card-body text-center py-3 px-2">
                    <div class="stat-icon text-primary mb-1">
                        <i class="bi bi-calendar-day"></i>
                    </div>
                    <div class="stat-value fs-4">{{ number_format($stundenHeute, 1, ',', '.') }}</div>
                    <div class="stat-label small">
                        <span class="d-none d-sm-inline">Heute / Oggi</span>
                        <span class="d-sm-none">Heute</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-4">
            <div class="card stat-card bg-white h-100">
                <div class="card-body text-center py-3 px-2">
                    <div class="stat-icon text-info mb-1">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <div class="stat-value fs-4">{{ number_format($stundenDieseWoche, 1, ',', '.') }}</div>
                    <div class="stat-label small">
                        <span class="d-none d-sm-inline">Woche / Settimana</span>
                        <span class="d-sm-none">Woche</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-4">
            <div class="card stat-card bg-white h-100">
                <div class="card-body text-center py-3 px-2">
                    <div class="stat-icon text-success mb-1">
                        <i class="bi bi-calendar-month"></i>
                    </div>
                    <div class="stat-value fs-4">{{ number_format($stundenDiesenMonat, 1, ',', '.') }}</div>
                    <div class="stat-label small">
                        <span class="d-none d-sm-inline">Monat / Mese</span>
                        <span class="d-sm-none">Monat</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Action Button (groß auf Mobile) --}}
    <div class="d-grid mb-4">
        <a href="{{ route('mitarbeiter.lohnstunden') }}" class="btn btn-success btn-lg py-3">
            <i class="bi bi-plus-circle fs-4"></i>
            <br class="d-sm-none">
            <span>Stunden erfassen / Registra ore</span>
        </a>
    </div>

    {{-- Letzte Einträge / Ultime voci --}}
    <div class="card">
        <div class="card-header py-2">
            <i class="bi bi-clock-history"></i> Letzte Einträge / Ultime voci
        </div>
        <div class="card-body p-0">
            @if($letzteEintraege->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($letzteEintraege as $eintrag)
                        <div class="list-group-item py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $eintrag->datum->format('d.m') }}</strong>
                                    <span class="badge bg-{{ $eintrag->typ === 'No' ? 'primary' : 'secondary' }} ms-1">{{ $eintrag->typ }}</span>
                                    @if($eintrag->notizen)
                                        <br><small class="text-muted">{{ Str::limit($eintrag->notizen, 25) }}</small>
                                    @endif
                                </div>
                                <span class="badge bg-dark rounded-pill fs-6">
                                    {{ number_format($eintrag->stunden, 1, ',', '.') }} h
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mb-0 mt-2">Noch keine Einträge / Nessuna voce ancora</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Datum / Data --}}
    <div class="text-center text-muted mt-4">
        <small>{{ now()->translatedFormat('l, d. F Y') }}</small>
    </div>
</div>
