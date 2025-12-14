{{-- resources/views/bank/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">

    {{-- Kopfzeile --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
        <div>
            <h5 class="mb-0">
                <i class="bi bi-receipt"></i> 
                Buchung #{{ $buchung->id }}
            </h5>
            <div class="mt-1">
                {!! $buchung->typ_badge !!}
                {!! $buchung->match_status_badge !!}
            </div>
        </div>
        <div class="btn-group">
            <a href="{{ route('bank.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> <span class="d-none d-sm-inline">Zurück</span>
            </a>
            <a href="{{ route('bank.matched') }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-check2-all"></i>
            </a>
        </div>
    </div>

    {{-- Betrag-Hero (Mobile) --}}
    <div class="card mb-3 d-md-none {{ $buchung->typ === 'CRDT' ? 'border-success' : 'border-danger' }}">
        <div class="card-body text-center py-3">
            <div class="fs-2 fw-bold {{ $buchung->typ === 'CRDT' ? 'text-success' : 'text-danger' }}">
                {{ $buchung->betrag_format }}
            </div>
            <div class="text-muted">
                {{ $buchung->buchungsdatum->format('d.m.Y') }}
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Buchungs-Details --}}
        <div class="col-lg-5 col-md-6">
            {{-- Details Card --}}
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Details</h6>
                </div>
                <div class="card-body py-2">
                    {{-- Desktop Betrag --}}
                    <div class="d-none d-md-block mb-2">
                        <span class="fs-3 fw-bold {{ $buchung->typ === 'CRDT' ? 'text-success' : 'text-danger' }}">
                            {{ $buchung->betrag_format }}
                        </span>
                    </div>
                    <div class="row g-2 small">
                        <div class="col-6">
                            <div class="text-muted">Buchungsdatum</div>
                            <div class="fw-medium">{{ $buchung->buchungsdatum->format('d.m.Y') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">Valutadatum</div>
                            <div class="fw-medium">{{ $buchung->valutadatum?->format('d.m.Y') ?? '–' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">Transaktionstyp</div>
                            <div class="fw-medium">{{ $buchung->tx_code_label }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">Status</div>
                            <div>{!! $buchung->match_status_badge !!}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Gegenkonto --}}
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="bi bi-person"></i> Gegenkonto</h6>
                </div>
                <div class="card-body py-2">
                    <div class="fw-bold mb-1">{{ $buchung->gegenkonto_name ?: '–' }}</div>
                    @if($buchung->gegenkonto_iban)
                        <code class="small d-block text-break">{{ $buchung->gegenkonto_iban }}</code>
                        
                        @php
                            $gebaeudeMatch = \App\Models\Gebaeude::where('bank_match_text_template', 'like', '%' . $buchung->gegenkonto_iban . '%')->first();
                        @endphp
                        <div class="mt-2">
                            @if($gebaeudeMatch)
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> IBAN bekannt
                                </span>
                                <div class="small text-muted mt-1">
                                    <a href="{{ route('gebaeude.edit', $gebaeudeMatch->id) }}">
                                        {{ $gebaeudeMatch->gebaeude_name ?: $gebaeudeMatch->codex }}
                                    </a>
                                </div>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="bi bi-question-circle"></i> IBAN unbekannt
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Verwendungszweck --}}
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="bi bi-chat-text"></i> Verwendungszweck</h6>
                </div>
                <div class="card-body py-2">
                    <p class="mb-0 small" style="white-space: pre-wrap; word-break: break-word;">{{ $buchung->verwendungszweck ?: '–' }}</p>
                </div>
            </div>

            {{-- Extrahierte Daten --}}
            @if(isset($extractedData) && (!empty($extractedData['nummern']) || !empty($extractedData['tokens']) || $extractedData['iban'] || $extractedData['cig']))
            <div class="card mb-3 border-info">
                <div class="card-header py-2 bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-search"></i> Erkannt</h6>
                </div>
                <div class="card-body py-2">
                    @if(!empty($extractedData['nummern']))
                    <div class="mb-2">
                        <span class="text-muted small">Mögliche RN:</span>
                        @foreach($extractedData['nummern'] as $num)
                            <span class="badge bg-primary">{{ $num }}</span>
                        @endforeach
                    </div>
                    @endif

                    @if(!empty($extractedData['tokens']))
                    <div class="mb-2">
                        <span class="text-muted small">Tokens:</span>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @foreach(array_slice($extractedData['tokens'], 0, 6) as $token)
                                <span class="badge bg-secondary">{{ $token }}</span>
                            @endforeach
                            @if(count($extractedData['tokens']) > 6)
                                <span class="badge bg-light text-dark">+{{ count($extractedData['tokens']) - 6 }}</span>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($extractedData['cig'])
                    <div class="mb-1">
                        <span class="text-muted small">CIG:</span>
                        <code>{{ $extractedData['cig'] }}</code>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Import-Info (Collapsible) --}}
            <div class="card mb-3">
                <div class="card-header py-2" data-bs-toggle="collapse" data-bs-target="#importInfo" role="button">
                    <h6 class="mb-0">
                        <i class="bi bi-database"></i> Import-Info
                        <i class="bi bi-chevron-down float-end"></i>
                    </h6>
                </div>
                <div class="collapse" id="importInfo">
                    <div class="card-body py-2 small">
                        <div class="row g-1">
                            <div class="col-4 text-muted">Datei</div>
                            <div class="col-8 text-truncate">{{ $buchung->import_datei }}</div>
                            <div class="col-4 text-muted">Import</div>
                            <div class="col-8">{{ $buchung->import_datum?->format('d.m.Y H:i') }}</div>
                            <div class="col-4 text-muted">IBAN</div>
                            <div class="col-8 text-truncate"><code>{{ $buchung->iban }}</code></div>
                            <div class="col-4 text-muted">Referenz</div>
                            <div class="col-8">{{ $buchung->ntry_ref }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Matching --}}
        <div class="col-lg-7 col-md-6">
            {{-- Bereits zugeordnet --}}
            @if($buchung->rechnung)
                @php
                    $matchInfo = json_decode($buchung->match_info, true) ?? [];
                    $score = $matchInfo['score'] ?? 0;
                    $details = $matchInfo['details'] ?? [];
                    $isAuto = ($matchInfo['auto'] ?? false) || $buchung->match_status === 'matched';
                @endphp
                <div class="card mb-3 border-success">
                    <div class="card-header py-2 bg-success text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-check-circle"></i> Zugeordnet</h6>
                        @if($score > 0)
                            <span class="badge bg-light text-dark">Score: {{ $score }}</span>
                        @endif
                    </div>
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="mb-0">{{ $buchung->rechnung->rechnungsnummer }}</h5>
                                <small class="text-muted">{{ $buchung->rechnung->rechnungsdatum?->format('d.m.Y') }}</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">{{ number_format($buchung->rechnung->erwarteter_zahlbetrag, 2, ',', '.') }} €</div>
                                <span class="badge bg-{{ $buchung->rechnung->status === 'paid' ? 'success' : 'warning' }}">
                                    {{ ucfirst($buchung->rechnung->status) }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="small mb-2">
                            <i class="bi bi-person"></i>
                            {{ $buchung->rechnung->re_name ?? ($buchung->rechnung->rechnungsempfaenger?->name ?? '–') }}
                        </div>
                        
                        @if($buchung->rechnung->geb_name)
                        <div class="small text-muted mb-2">
                            <i class="bi bi-building"></i>
                            {{ $buchung->rechnung->geb_name }}
                        </div>
                        @endif
                        
                        {{-- Match-Details --}}
                        @if(!empty($details))
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            @foreach($details as $detail)
                                <span class="badge bg-light text-dark" title="{{ $detail['text'] }}">
                                    +{{ $detail['punkte'] }}
                                </span>
                            @endforeach
                        </div>
                        @endif
                        
                        <div class="small text-muted mb-2">
                            {{ $isAuto ? 'Auto' : 'Manuell' }} · {{ $buchung->matched_at?->format('d.m.Y H:i') }}
                        </div>
                        
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('rechnung.edit', $buchung->rechnung_id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye"></i> Rechnung
                            </a>
                            <form method="POST" action="{{ route('bank.unmatch', $buchung->id) }}" 
                                  onsubmit="return confirm('Zuordnung aufheben?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-x-circle"></i> Aufheben
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Manuelles Matching --}}
            @if($buchung->match_status === 'unmatched' && $buchung->typ === 'CRDT')
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-link-45deg"></i> Matches</h6>
                        <small class="text-muted">Auto ab {{ $autoMatchThreshold }}</small>
                    </div>
                    <div class="card-body py-2">
                        <div class="alert alert-info py-2 small mb-2">
                            <i class="bi bi-info-circle"></i>
                            Graue Einträge = bereits bezahlt (für historische Verknüpfungen)
                        </div>

                        @if($potentielleMatches->isNotEmpty())
                            <div class="list-group list-group-flush">
                                @foreach($potentielleMatches as $match)
                                    @php
                                        $re = $match['rechnung'];
                                        $score = $match['score'];
                                        $details = $match['details'];
                                        $isPaid = $match['is_paid'] ?? false;
                                    @endphp
                                    <div class="list-group-item px-0 py-2 {{ $isPaid ? 'bg-light' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1 me-2" style="min-width: 0;">
                                                {{-- Header --}}
                                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-1">
                                                    <div>
                                                        <strong>{{ $re->rechnungsnummer }}</strong>
                                                        @if($isPaid)
                                                            <span class="badge bg-secondary">Bezahlt</span>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-{{ $score >= $autoMatchThreshold ? 'success' : ($score >= 50 ? 'warning' : 'secondary') }}">
                                                            {{ $score }}
                                                        </span>
                                                        <span class="text-success fw-bold">
                                                            {{ number_format($re->erwarteter_zahlbetrag, 2, ',', '.') }}€
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                {{-- Empfänger --}}
                                                <div class="small text-truncate">
                                                    <i class="bi bi-person"></i>
                                                    {{ $re->re_name ?: ($re->rechnungsempfaenger?->name ?? '–') }}
                                                </div>
                                                
                                                {{-- Gebäude --}}
                                                @if($re->geb_name || $re->geb_codex)
                                                <div class="small text-muted text-truncate">
                                                    <i class="bi bi-building"></i>
                                                    {{ $re->geb_name ?? '' }}
                                                    @if($re->geb_codex) <code>{{ $re->geb_codex }}</code> @endif
                                                </div>
                                                @endif
                                                
                                                {{-- Match-Gründe --}}
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    @foreach(array_slice($details, 0, 4) as $detail)
                                                        @php
                                                            $isNegative = ($detail['punkte'] ?? 0) < 0;
                                                            $badgeClass = $isNegative ? 'bg-danger text-white' : 'bg-light text-dark';
                                                        @endphp
                                                        <span class="badge {{ $badgeClass }}" style="font-size: 0.65rem;">
                                                            {{ $isNegative ? '' : '+' }}{{ $detail['punkte'] }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            
                                            <form method="POST" action="{{ route('bank.match', $buchung->id) }}">
                                                @csrf
                                                <input type="hidden" name="rechnung_id" value="{{ $re->id }}">
                                                <input type="hidden" name="mark_paid" value="{{ $isPaid ? '0' : '1' }}">
                                                <input type="hidden" name="save_iban" value="1">
                                                <button type="submit" class="btn btn-{{ $isPaid ? 'outline-secondary' : 'success' }} btn-sm">
                                                    <i class="bi bi-{{ $isPaid ? 'link' : 'check-lg' }}"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted small mb-2">Keine passenden Rechnungen gefunden.</p>
                        @endif

                        <hr class="my-2">

                        {{-- Suche --}}
                        <form method="GET" action="{{ route('bank.show', $buchung->id) }}" class="mb-2">
                            <div class="input-group input-group-sm">
                                <input type="text" name="q" class="form-control" 
                                       placeholder="Suche: Name, Nummer, Codex..."
                                       value="{{ request('q') }}">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>

                        @if(request('q'))
                            @php
                                $suchbegriff = request('q');
                                $suchergebnisse = \App\Models\Rechnung::with('rechnungsempfaenger')
                                    ->where(function ($q) use ($suchbegriff) {
                                        if (is_numeric($suchbegriff)) {
                                            $q->where('laufnummer', $suchbegriff);
                                        }
                                        if (preg_match('/^(\d{4})\/(\d+)$/', $suchbegriff, $m)) {
                                            $q->orWhere(function ($q2) use ($m) {
                                                $q2->where('jahr', $m[1])->where('laufnummer', $m[2]);
                                            });
                                        }
                                        $q->orWhere('re_name', 'like', "%{$suchbegriff}%")
                                          ->orWhere('geb_name', 'like', "%{$suchbegriff}%")
                                          ->orWhere('geb_codex', 'like', "%{$suchbegriff}%")
                                          ->orWhereHas('rechnungsempfaenger', fn($q2) => $q2->where('name', 'like', "%{$suchbegriff}%"));
                                    })
                                    ->orderByDesc('rechnungsdatum')
                                    ->take(10)
                                    ->get();
                            @endphp

                            @if($suchergebnisse->isNotEmpty())
                                @foreach($suchergebnisse as $re)
                                    @php $isPaid = $re->status === 'paid'; @endphp
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom {{ $isPaid ? 'bg-light' : '' }}">
                                        <div class="flex-grow-1 me-2" style="min-width: 0;">
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ $re->rechnungsnummer }}</strong>
                                                @if($isPaid) <span class="badge bg-secondary">Bezahlt</span> @endif
                                                <span class="fw-bold">{{ number_format($re->erwarteter_zahlbetrag, 2, ',', '.') }}€</span>
                                            </div>
                                            <div class="small text-truncate">
                                                {{ $re->re_name ?: ($re->rechnungsempfaenger?->name ?? '–') }}
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('bank.match', $buchung->id) }}">
                                            @csrf
                                            <input type="hidden" name="rechnung_id" value="{{ $re->id }}">
                                            <input type="hidden" name="mark_paid" value="{{ $isPaid ? '0' : '1' }}">
                                            <input type="hidden" name="save_iban" value="1">
                                            <button type="submit" class="btn btn-sm btn-{{ $isPaid ? 'outline-secondary' : 'success' }}">
                                                <i class="bi bi-{{ $isPaid ? 'link' : 'check' }}"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted small">Nichts gefunden.</p>
                            @endif
                            <hr class="my-2">
                        @endif

                        {{-- Direkte ID-Eingabe --}}
                        <form method="POST" action="{{ route('bank.match', $buchung->id) }}">
                            @csrf
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">ID</span>
                                <input type="number" name="rechnung_id" class="form-control" placeholder="Rechnung-ID">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-link"></i>
                                </button>
                            </div>
                            <div class="d-flex flex-wrap gap-3 small">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="mark_paid" value="1" id="markPaid" checked>
                                    <label class="form-check-label" for="markPaid">Als bezahlt</label>
                                </div>
                                @if($buchung->gegenkonto_iban)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="save_iban" value="1" id="saveIban" checked>
                                    <label class="form-check-label" for="saveIban">IBAN speichern</label>
                                </div>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Ignorieren --}}
                <div class="card">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="bi bi-x-circle"></i> Ignorieren</h6>
                    </div>
                    <div class="card-body py-2">
                        <form method="POST" action="{{ route('bank.ignore', $buchung->id) }}">
                            @csrf
                            <div class="input-group input-group-sm">
                                <input type="text" name="bemerkung" class="form-control" placeholder="Bemerkung (optional)">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="bi bi-x"></i> Ignorieren
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
