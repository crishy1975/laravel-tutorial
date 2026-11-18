{{-- ═══════════════════════════════════════════════════════════════
     ZIEL: resources/views/reinigungsplanung/meldungen.blade.php
     Meldung nicht erreichter Kunden an die Gemeinden
     ═══════════════════════════════════════════════════════════════ --}}
@extends('layouts.app')

@section('title', 'Meldungen an Gemeinden')

@section('content')
<style>
    .meldung-text { font-family: inherit; font-size: .9rem; min-height: 320px; }
    .meldung-gebaeude { max-height: 260px; overflow-y: auto; }
    .meldung-gebaeude .form-check { padding: .25rem .25rem .25rem 2rem; border-bottom: 1px solid #f1f3f5; }
    .meldung-gebaeude .form-check:last-child { border-bottom: 0; }
    .card-header-gemeinde { background: #f8d7da; }
    .card-header-ohne { background: #fff3cd; }
</style>

<div class="container-fluid py-2 py-md-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-1">
                <i class="bi bi-envelope-exclamation text-danger"></i>
                Meldungen an Gemeinden
            </h1>
            <p class="text-muted small mb-0">Nicht erreichte Kunden, die noch nicht gemeldet wurden – gruppiert nach Gemeinde.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('gemeinden.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-buildings"></i> Gemeinden &amp; Vorlagen
            </a>
            <a href="{{ route('reinigungsplanung.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left"></i> Reinigungsplanung
            </a>
        </div>
    </div>

    @foreach(['success' => 'success', 'error' => 'danger'] as $key => $farbe)
        @if(session($key))
            <div class="alert alert-{{ $farbe }} py-2">{{ session($key) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger py-2">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    @if($gruppen->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-check2-circle display-4 text-success"></i>
                <p class="mt-2 mb-0 text-muted">Keine offenen Meldungen. Gebäude markierst du in der Reinigungsplanung über ⋮ → „Nicht erreicht“.</p>
            </div>
        </div>
    @endif

    @foreach($gruppen as $i => $gr)
        @php
            $gemeinde = $gr['gemeinde'];
        @endphp

        <div class="card shadow-sm mb-4">
            <div class="card-header {{ $gemeinde ? 'card-header-gemeinde' : 'card-header-ohne' }} d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0">
                    @if($gemeinde)
                        <i class="bi bi-bank"></i> Gemeinde {{ $gemeinde->name }}
                    @else
                        <i class="bi bi-exclamation-triangle"></i> Keine Gemeinde zugeordnet
                    @endif
                    <span class="badge bg-danger ms-1">{{ $gr['gebaeude']->count() }}</span>
                </h6>
                @unless($gemeinde)
                    <small>
                        Orte: <strong>{{ $gr['gebaeude']->pluck('wohnort')->filter()->unique()->implode(', ') ?: 'ohne Ort' }}</strong>
                        – bitte unter <a href="{{ route('gemeinden.index') }}">Gemeinden</a> anlegen oder als Alias eintragen.
                    </small>
                @endunless
            </div>

            <div class="card-body">
                @if($gemeinde)
                <form method="POST" action="{{ route('reinigungsplanung.meldungen.senden') }}" class="meldung-form">
                    @csrf
                    <input type="hidden" name="gemeinde_id" value="{{ $gemeinde->id }}">

                    <div class="row g-3">
                        {{-- Links: Gebäude --}}
                        <div class="col-12 col-lg-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold mb-0">Gebäude</label>
                                <a href="#" class="small meldung-alle" data-ziel="gr{{ $i }}">alle / keine</a>
                            </div>
                            <div class="meldung-gebaeude border rounded" id="gr{{ $i }}">
                                @foreach($gr['gebaeude'] as $g)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="gebaeude_ids[]"
                                               value="{{ $g->id }}" id="g{{ $i }}_{{ $g->id }}" checked>
                                        <label class="form-check-label small" for="g{{ $i }}_{{ $g->id }}">
                                            <strong>{{ $g->codex }}</strong> {{ $g->gebaeude_name }}<br>
                                            <span class="text-muted">
                                                {{ trim($g->strasse . ' ' . $g->hausnummer) }}@if($g->intern) / Int. {{ $g->intern }}@endif,
                                                {{ $g->wohnort }}
                                                · nicht erreicht {{ $g->nicht_erreicht_am?->format('d.m.Y') }}
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-1">
                                Die Liste im Text wird beim Öffnen erzeugt. Wenn du Gebäude abwählst, entferne sie bitte auch im Text.
                            </small>
                        </div>

                        {{-- Rechts: Mail --}}
                        <div class="col-12 col-lg-8">
                            <div class="row g-2 mb-2">
                                <div class="col-12 col-md-5">
                                    <label class="form-label small fw-bold mb-1">
                                        Empfänger
                                        <span class="badge versand-badge"></span>
                                    </label>
                                    <input type="email" name="empfaenger" class="form-control form-control-sm empfaenger-input"
                                           data-gemeinde-pec="{{ $gemeinde->pec }}"
                                           value="{{ old('empfaenger', $gr['empfaenger']) }}" placeholder="pec@gemeinde.it">
                                    <small class="text-muted versand-hinweis"></small>
                                </div>
                                <div class="col-12 col-md-7">
                                    <label class="form-label small fw-bold mb-1">Betreff</label>
                                    <input type="text" name="betreff" class="form-control form-control-sm"
                                           value="{{ $gr['betreff'] }}" maxlength="255" required>
                                </div>
                            </div>

                            <label class="form-label small fw-bold mb-1">Text <span class="fw-normal text-muted">(frei bearbeitbar)</span></label>
                            <textarea name="text" class="form-control meldung-text" required>{{ $gr['text'] }}</textarea>

                            <div class="d-flex justify-content-end gap-2 mt-2 flex-wrap">
                                <button type="submit" name="aktion" value="manuell" class="btn btn-outline-secondary btn-sm"
                                        onclick="return confirm('Ohne Versand als gemeldet markieren (z. B. wenn per Post/Hand gemeldet)?')">
                                    <i class="bi bi-check2"></i> Nur als gemeldet markieren
                                </button>
                                <button type="submit" name="aktion" value="senden" class="btn btn-danger btn-sm"
                                        onclick="return meldungBestaetigen(this.form)">
                                    <i class="bi bi-send"></i> Meldung senden
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                @else
                    <ul class="small mb-0">
                        @foreach($gr['gebaeude'] as $g)
                            <li>
                                <strong>{{ $g->codex }}</strong> {{ $g->gebaeude_name }} –
                                {{ trim($g->strasse . ' ' . $g->hausnummer) }}, {{ $g->wohnort ?: 'kein Ort eingetragen' }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endforeach

    {{-- Letzte Meldungen (Nachweis) --}}
    @if($letzteMeldungen->isNotEmpty())
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 small"><i class="bi bi-clock-history"></i> Letzte Meldungen</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 small align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Datum</th>
                            <th>Gemeinde</th>
                            <th>Empfänger</th>
                            <th class="text-center">Gebäude</th>
                            <th>Art</th>
                            <th>Von</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($letzteMeldungen as $m)
                            <tr>
                                <td class="text-nowrap">{{ $m->gesendet_am->format('d.m.Y H:i') }}</td>
                                <td>{{ $m->gemeinde?->name ?? '–' }}</td>
                                <td>{{ $m->empfaenger ?: '–' }}</td>
                                <td class="text-center">{{ count($m->gebaeude_ids ?? []) }}</td>
                                <td>
                                    @if($m->versandart === 'pec')
                                        <span class="badge bg-success">PEC</span>
                                    @elseif($m->versandart === 'mail')
                                        <span class="badge bg-primary">E-Mail</span>
                                    @else
                                        <span class="badge bg-secondary">manuell</span>
                                    @endif
                                </td>
                                <td>{{ $m->user?->name ?? '–' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('reinigungsplanung.meldungen.show', $m) }}" target="_blank" class="btn btn-outline-secondary btn-sm py-0">
                                        <i class="bi bi-file-text"></i> Text
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script>
document.querySelectorAll('.meldung-alle').forEach(a => {
    a.addEventListener('click', function(e) {
        e.preventDefault();
        const box = document.getElementById(this.dataset.ziel);
        const checks = box.querySelectorAll('input[type=checkbox]');
        const alleAn = [...checks].every(c => c.checked);
        checks.forEach(c => c.checked = !alleAn);
    });
});

// ⭐ Versandweg anzeigen: PEC-Empfänger → über PEC, sonst normale E-Mail
function istPec(adresse, gemeindePec) {
    adresse = (adresse || '').trim().toLowerCase();
    if (!adresse) return false;
    if (gemeindePec && gemeindePec.trim().toLowerCase() === adresse) return true;
    const domain = adresse.split('@')[1] || '';
    return /(^|\.)(pec|legalmail|postacert|cert|cgn|pecsicura|registerpec)\./.test(domain);
}

function versandAnzeigen(input) {
    const box    = input.closest('.col-12');
    const badge  = box.querySelector('.versand-badge');
    const hinweis = box.querySelector('.versand-hinweis');
    if (!input.value.trim()) {
        badge.className = 'badge versand-badge d-none';
        hinweis.textContent = '';
        return;
    }
    if (istPec(input.value, input.dataset.gemeindePec)) {
        badge.className = 'badge versand-badge bg-success';
        badge.textContent = 'PEC';
        hinweis.textContent = 'Versand über resch@pec.it';
    } else {
        badge.className = 'badge versand-badge bg-primary';
        badge.textContent = 'E-Mail';
        hinweis.textContent = 'Versand über die normale Firmen-Mail (keine PEC)';
    }
}

document.querySelectorAll('.empfaenger-input').forEach(inp => {
    versandAnzeigen(inp);
    inp.addEventListener('input', () => versandAnzeigen(inp));
});

function meldungBestaetigen(form) {
    const anzahl = form.querySelectorAll('input[name="gebaeude_ids[]"]:checked').length;
    const empf = form.querySelector('input[name="empfaenger"]').value.trim();
    if (!anzahl) { alert('Bitte mindestens ein Gebäude auswählen.'); return false; }
    if (!empf)   { alert('Bitte einen Empfänger eintragen.'); return false; }
    const inp = form.querySelector('.empfaenger-input');
    const weg = istPec(empf, inp.dataset.gemeindePec) ? 'per PEC' : 'per normaler E-Mail';
    return confirm('Meldung mit ' + anzahl + ' Gebäude(n) jetzt ' + weg + ' an ' + empf + ' senden?');
}
</script>
@endsection
