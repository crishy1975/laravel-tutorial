{{-- resources/views/bank/matched.blade.php --}}
{{-- Kontrollansicht: Bereits zugeordnete Buchungen --}}
@extends('layouts.app')

@section('content')
@php
    /**
     * Bereinigt Text: Trimmt, entfernt mehrfache Leerzeichen und überflüssige Zeilenumbrüche.
     */
    function cleanText(?string $text): string {
        if (empty($text)) return '';
        // Mehrfache Leerzeichen/Tabs → ein Leerzeichen
        $text = preg_replace('/[^\S\n]+/', ' ', $text);
        // Mehrfache Zeilenumbrüche → maximal einer
        $text = preg_replace('/\n{2,}/', "\n", $text);
        return trim($text);
    }

    /**
     * Markiert Wörter im Text, die auch im Empfänger-Namen vorkommen.
     * Ignoriert kurze Wörter (< 3 Zeichen) um Rauschen zu vermeiden.
     */
    function highlightMatchingWords(string $text, ?string $empfaenger): string {
        $text = cleanText($text);
        $empfaenger = cleanText($empfaenger);

        if (empty($empfaenger) || empty($text)) {
            return e($text ?: '–');
        }

        // Empfänger in Wörter zerlegen – Anführungszeichen, Klammern, Punkte etc. entfernen
        $empfaengerWords = preg_split('/[\s\-\/.,;:\"\'\"\"()\[\]{}#*+]+/u', $empfaenger, -1, PREG_SPLIT_NO_EMPTY);
        $empfaengerWords = array_filter($empfaengerWords, fn($w) => mb_strlen($w) >= 3);
        $empfaengerWords = array_values($empfaengerWords);

        if (empty($empfaengerWords)) {
            return e($text);
        }

        // Regex-Pattern bauen (case-insensitive, ohne \b für bessere Trefferquote)
        $escaped = array_map(fn($w) => preg_quote($w, '/'), $empfaengerWords);
        $pattern = '/(' . implode('|', $escaped) . ')/iu';

        // Splitten und Treffer markieren
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        $result = '';
        foreach ($parts as $part) {
            if (preg_match($pattern, $part)) {
                $result .= '<mark class="px-0 py-0 bg-warning bg-opacity-50 rounded-1">' . e($part) . '</mark>';
            } else {
                $result .= e($part);
            }
        }

        return $result;
    }
@endphp
<div class="container-fluid py-3">

    {{-- Kopfzeile --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
        <div>
            <h5 class="mb-0"><i class="bi bi-check2-all"></i> Zugeordnete Buchungen</h5>
            <small class="text-muted">Kontrollansicht der automatischen und manuellen Zuordnungen</small>
        </div>
        <div class="btn-group">
            <a href="{{ route('bank.autoMatchProgress') }}" class="btn btn-success btn-sm">
                <i class="bi bi-magic"></i> <span class="d-none d-sm-inline">Auto-Match</span>
            </a>
            <a href="{{ route('bank.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
    </div>

    {{-- Statistik --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="fs-4 fw-bold text-success">{{ $stats['gesamt'] ?? 0 }}</div>
                    <small class="text-muted">Gesamt</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="fs-4 fw-bold text-primary">{{ $stats['auto'] ?? 0 }}</div>
                    <small class="text-muted">Automatisch</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="fs-4 fw-bold text-info">{{ $stats['manuell'] ?? 0 }}</div>
                    <small class="text-muted">Manuell</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="fs-4 fw-bold text-warning">
                        {{ number_format($stats['summe'] ?? 0, 2, ',', '.') }}€
                    </div>
                    <small class="text-muted">Summe</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash-Nachricht --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="zeitraum" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Alle Zeiträume</option>
                        <option value="heute" {{ ($filter['zeitraum'] ?? '') === 'heute' ? 'selected' : '' }}>Heute</option>
                        <option value="woche" {{ ($filter['zeitraum'] ?? '') === 'woche' ? 'selected' : '' }}>Letzte Woche</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="typ" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Alle Typen</option>
                        <option value="matched" {{ ($filter['typ'] ?? '') === 'matched' ? 'selected' : '' }}>Automatisch</option>
                        <option value="manual" {{ ($filter['typ'] ?? '') === 'manual' ? 'selected' : '' }}>Manuell</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="verified" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Alle Status</option>
                        <option value="ja" {{ ($filter['verified'] ?? '') === 'ja' ? 'selected' : '' }}>✓ Geprüft</option>
                        <option value="nein" {{ ($filter['verified'] ?? '') === 'nein' ? 'selected' : '' }}>✗ Ungeprüft</option>
                    </select>
                </div>
                @if(!empty($filter['zeitraum']) || !empty($filter['typ']) || !empty($filter['verified']))
                    <div class="col-auto">
                        <a href="{{ route('bank.matched') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x"></i> Reset
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Tabelle (Desktop) --}}
    <div class="card d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Buchung</th>
                        <th>Betrag</th>
                        <th>Rechnung</th>
                        <th>Empfänger</th>
                        <th class="text-center">Score</th>
                        <th class="text-center">Typ</th>
                        <th>Zugeordnet</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($buchungen as $buchung)
                        @php
                            $rechnung = $buchung->rechnung;
                            $matchInfo = json_decode($buchung->match_info, true) ?? [];
                            $score = $matchInfo['score'] ?? 0;
                            $isAuto = $buchung->match_status === 'matched';
                            $empfaengerName = $rechnung
                                ? ($rechnung->re_name ?: ($rechnung->rechnungsempfaenger?->name ?? ''))
                                : '';
                            $rechnungsBetrag = $rechnung ? ($rechnung->erwarteter_zahlbetrag ?? $rechnung->brutto_summe) : null;
                            $betragStimmt = $rechnungsBetrag === null || abs($buchung->betrag - $rechnungsBetrag) < 0.01;
                        @endphp
                        <tr class="{{ $buchung->is_verified ? 'table-success' : '' }}" id="row-{{ $buchung->id }}">
                            {{-- Buchung --}}
                            <td>
                                <div class="fw-medium">{{ $buchung->buchungsdatum->format('d.m.Y') }}</div>
                                <small class="text-muted">
                                    {{ cleanText($buchung->gegenkonto_name) ?: '–' }}
                                </small>
                                <div class="small text-muted mt-1" style="word-break: break-word;">
                                    {!! highlightMatchingWords($buchung->verwendungszweck ?? '', $empfaengerName) !!}
                                </div>
                            </td>

                            {{-- Betrag --}}
                            <td>
                                <span class="fw-bold {{ $betragStimmt ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($buchung->betrag, 2, ',', '.') }}€
                                </span>
                                @if(!$betragStimmt)
                                    <div class="small text-danger">
                                        <i class="bi bi-exclamation-triangle"></i> Differenz: {{ number_format($buchung->betrag - $rechnungsBetrag, 2, ',', '.') }}€
                                    </div>
                                @endif
                            </td>

                            {{-- Rechnung --}}
                            <td>
                                @if($rechnung)
                                    <a href="{{ route('rechnung.edit', $rechnung->id) }}" class="fw-medium text-decoration-none">
                                        {{ $rechnung->rechnungsnummer }}
                                    </a>
                                    <div class="small text-muted">
                                        {{ number_format($rechnung->erwarteter_zahlbetrag ?? $rechnung->brutto_summe, 2, ',', '.') }}€
                                    </div>
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>

                            {{-- Empfänger --}}
                            <td>
                                @if($rechnung)
                                    <span class="text-truncate d-block" style="max-width: 180px;">
                                        {{ $rechnung->re_name ?: ($rechnung->rechnungsempfaenger?->name ?? '–') }}
                                    </span>
                                    @if($rechnung->geb_codex)
                                        <code class="small">{{ $rechnung->geb_codex }}</code>
                                    @endif
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>

                            {{-- Score --}}
                            <td class="text-center">
                                @if($score > 0)
                                    <span class="badge bg-{{ $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'secondary') }}">
                                        {{ $score }}
                                    </span>
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>

                            {{-- Typ --}}
                            <td class="text-center">
                                <span class="badge bg-{{ $isAuto ? 'primary' : 'info' }}">
                                    {{ $isAuto ? 'Auto' : 'Manuell' }}
                                </span>
                            </td>

                            {{-- Datum --}}
                            <td>
                                <small class="text-muted">
                                    {{ $buchung->matched_at?->format('d.m.Y H:i') ?? '–' }}
                                </small>
                            </td>

                            {{-- Aktionen --}}
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            class="btn btn-{{ $buchung->is_verified ? 'success' : 'outline-secondary' }} btn-verify"
                                            data-id="{{ $buchung->id }}"
                                            title="{{ $buchung->is_verified ? 'Geprüft ✓' : 'Als geprüft markieren' }}">
                                        <i class="bi bi-{{ $buchung->is_verified ? 'check-circle-fill' : 'check-circle' }}"></i>
                                    </button>
                                    <a href="{{ route('bank.show', $buchung->id) }}" class="btn btn-outline-primary" title="Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form method="POST" action="{{ route('bank.unmatch', $buchung->id) }}" 
                                          onsubmit="return confirm('Zuordnung aufheben?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Aufheben">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1"></i>
                                <p class="mb-0 mt-2">Noch keine Zuordnungen</p>
                                <a href="{{ route('bank.autoMatchProgress') }}" class="btn btn-success btn-sm mt-2">
                                    <i class="bi bi-magic"></i> Auto-Match starten
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Karten (Mobile) --}}
    <div class="d-md-none">
        @forelse($buchungen as $buchung)
            @php
                $rechnung = $buchung->rechnung;
                $matchInfo = json_decode($buchung->match_info, true) ?? [];
                $score = $matchInfo['score'] ?? 0;
                $isAuto = $buchung->match_status === 'matched';
                $empfaengerName = $rechnung
                    ? ($rechnung->re_name ?: ($rechnung->rechnungsempfaenger?->name ?? ''))
                    : '';
                $rechnungsBetrag = $rechnung ? ($rechnung->erwarteter_zahlbetrag ?? $rechnung->brutto_summe) : null;
                $betragStimmt = $rechnungsBetrag === null || abs($buchung->betrag - $rechnungsBetrag) < 0.01;
            @endphp
            <div class="card mb-2 {{ $buchung->is_verified ? 'border-success' : '' }}" id="card-{{ $buchung->id }}">
                <div class="card-body py-2 px-3 {{ $buchung->is_verified ? 'bg-success bg-opacity-10' : '' }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="fw-bold {{ $betragStimmt ? 'text-success' : 'text-danger' }} fs-5">
                                {{ number_format($buchung->betrag, 2, ',', '.') }}€
                            </span>
                            @if(!$betragStimmt)
                                <i class="bi bi-exclamation-triangle text-danger"></i>
                            @endif
                            <span class="badge bg-{{ $isAuto ? 'primary' : 'info' }} ms-1">
                                {{ $isAuto ? 'Auto' : 'Manuell' }}
                            </span>
                        </div>
                        @if($score > 0)
                            <span class="badge bg-{{ $score >= 80 ? 'success' : 'warning' }}">
                                Score: {{ $score }}
                            </span>
                        @endif
                    </div>

                    @if($rechnung)
                        <div class="mb-2">
                            <a href="{{ route('rechnung.edit', $rechnung->id) }}" class="fw-medium text-decoration-none">
                                <i class="bi bi-receipt"></i> {{ $rechnung->rechnungsnummer }}
                            </a>
                            <span class="text-muted ms-2">
                                {{ number_format($rechnung->erwarteter_zahlbetrag ?? $rechnung->brutto_summe, 2, ',', '.') }}€
                            </span>
                        </div>
                        <div class="small text-truncate">
                            <i class="bi bi-person"></i>
                            {{ $rechnung->re_name ?: ($rechnung->rechnungsempfaenger?->name ?? '–') }}
                        </div>
                    @endif

                    <div class="small text-muted mt-1">
                        <i class="bi bi-calendar"></i> {{ $buchung->buchungsdatum->format('d.m.Y') }}
                        → {{ $buchung->matched_at?->format('d.m.Y H:i') ?? '' }}
                    </div>

                    {{-- Verwendungszweck --}}
                    <div class="small text-muted mt-2 p-2 bg-light rounded" style="word-break: break-word;">
                        {!! highlightMatchingWords($buchung->verwendungszweck ?? '', $empfaengerName) !!}
                    </div>

                    <div class="d-flex justify-content-end gap-1 mt-2">
                        <button type="button" 
                                class="btn btn-sm btn-{{ $buchung->is_verified ? 'success' : 'outline-secondary' }} btn-verify"
                                data-id="{{ $buchung->id }}"
                                title="{{ $buchung->is_verified ? 'Geprüft ✓' : 'Als geprüft markieren' }}">
                            <i class="bi bi-{{ $buchung->is_verified ? 'check-circle-fill' : 'check-circle' }}"></i>
                        </button>
                        <a href="{{ route('bank.show', $buchung->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <form method="POST" action="{{ route('bank.unmatch', $buchung->id) }}" 
                              onsubmit="return confirm('Zuordnung aufheben?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info text-center">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mb-0 mt-2">Noch keine Zuordnungen</p>
                <a href="{{ route('bank.autoMatchProgress') }}" class="btn btn-success btn-sm mt-2">
                    <i class="bi bi-magic"></i> Auto-Match starten
                </a>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($buchungen->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $buchungen->links() }}
        </div>
    @endif

</div>

@push('scripts')
<script>
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-verify');
        if (!btn) return;

        const id = btn.dataset.id;
        const url = '{{ route("bank.verify", ":id") }}'.replace(':id', id);
        btn.disabled = true;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        })
        .then(r => {
            if (!r.ok) throw new Error('Status ' + r.status);
            return r.json();
        })
        .then(data => {
            const icon = btn.querySelector('i');
            const row = document.getElementById('row-' + id);
            const card = document.getElementById('card-' + id);
            const cardBody = card?.querySelector('.card-body');

            if (data.verified) {
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-success');
                btn.title = 'Geprüft ✓';
                icon.classList.remove('bi-check-circle');
                icon.classList.add('bi-check-circle-fill');
                // Zeile/Karte grün markieren
                row?.classList.add('table-success');
                card?.classList.add('border-success');
                cardBody?.classList.add('bg-success', 'bg-opacity-10');
            } else {
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
                btn.title = 'Als geprüft markieren';
                icon.classList.remove('bi-check-circle-fill');
                icon.classList.add('bi-check-circle');
                // Farbe entfernen
                row?.classList.remove('table-success');
                card?.classList.remove('border-success');
                cardBody?.classList.remove('bg-success', 'bg-opacity-10');
            }
        })
        .catch(err => {
            console.error('Verify-Fehler:', err);
            alert('Fehler beim Speichern: ' + err.message);
        })
        .finally(() => btn.disabled = false);
    });
</script>
@endpush
@endsection
