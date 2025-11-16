{{-- resources/views/rechnung/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">

  {{-- Kopfzeile --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-receipt"></i> Rechnungen</h3>
    <div class="d-flex gap-2">
      <a href="{{ route('rechnung.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Neue Rechnung
      </a>
    </div>
  </div>

  {{-- Flash Messages --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Filter --}}
  <form method="GET" action="{{ route('rechnung.index') }}" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
      
      {{-- Jahr --}}
      <div class="col-md-2">
        <label class="form-label mb-1">Jahr</label>
        <select name="jahr" class="form-select">
          @foreach($jahre as $j)
            <option value="{{ $j }}" {{ $jahr == $j ? 'selected' : '' }}>
              {{ $j }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Status --}}
      <div class="col-md-2">
        <label class="form-label mb-1">Status</label>
        <select name="status" class="form-select">
          <option value="">Alle</option>
          <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Entwurf</option>
          <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Versendet</option>
          <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Bezahlt</option>
          <option value="overdue" {{ $status === 'overdue' ? 'selected' : '' }}>Überfällig</option>
          <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Storniert</option>
        </select>
      </div>

      {{-- Gebäude --}}
      <div class="col-md-3">
        <label class="form-label mb-1">Gebäude</label>
        <select name="gebaeude_id" class="form-select">
          <option value="">Alle Gebäude</option>
          @foreach($gebaeudeFilter as $g)
            <option value="{{ $g->id }}" {{ $gebaeude_id == $g->id ? 'selected' : '' }}>
              {{ $g->codex }} - {{ $g->gebaeude_name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Suche --}}
      <div class="col-md-3">
        <label class="form-label mb-1">Suche (Nr./Kunde)</label>
        <input type="text" name="suche" class="form-control" 
               value="{{ $suche }}" placeholder="z.B. 0042 oder Müller">
      </div>

      {{-- Buttons --}}
      <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100" type="submit">
          <i class="bi bi-search"></i> Suchen
        </button>
      </div>

      @if($jahr || $status || $gebaeude_id || $suche)
        <div class="col-md-2">
          <a href="{{ route('rechnung.index') }}" class="btn btn-outline-dark w-100">
            <i class="bi bi-x-circle"></i> Reset
          </a>
        </div>
      @endif
    </div>
  </form>

  {{-- Tabelle --}}
  @if($rechnungen->isEmpty())
    <div class="alert alert-info">Keine Rechnungen gefunden.</div>
  @else
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Nummer</th>
              <th>Datum</th>
              <th>Kunde</th>
              <th>Gebäude</th>
              <th class="text-end">Zahlbar</th>
              <th>Status</th>
              <th class="text-end">Aktionen</th>
            </tr>
          </thead>
          <tbody>
            @foreach($rechnungen as $r)
              <tr>
                {{-- Nummer --}}
                <td>
                  <a href="{{ route('rechnung.show', $r->id) }}" class="text-decoration-none fw-semibold">
                    {{ $r->nummern }}
                  </a>
                </td>

                {{-- Datum --}}
                <td>{{ $r->rechnungsdatum?->format('d.m.Y') }}</td>

                {{-- Kunde (Snapshot) --}}
                <td>
                  <div class="small">
                    {{ $r->re_name }}
                    @if($r->re_wohnort)
                      <br><span class="text-muted">{{ $r->re_wohnort }}</span>
                    @endif
                  </div>
                </td>

                {{-- Gebäude (Snapshot) --}}
                <td>
                  @if($r->gebaeude)
                    <a href="{{ route('gebaeude.edit', $r->gebaeude_id) }}" 
                       class="text-decoration-none small">
                      {{ $r->geb_codex }}
                    </a>
                  @else
                    <span class="text-muted small">{{ $r->geb_codex }}</span>
                  @endif
                </td>

                {{-- Zahlbar --}}
                <td class="text-end">
                  <strong>{{ number_format($r->zahlbar_betrag, 2, ',', '.') }} €</strong>
                  @if($r->zahlungsziel && $r->status !== 'paid')
                    <br><small class="text-muted">bis {{ $r->zahlungsziel->format('d.m.Y') }}</small>
                  @endif
                </td>

                {{-- Status --}}
                <td>{!! $r->status_badge !!}</td>

                {{-- Aktionen --}}
                <td class="text-end">
                  <div class="btn-group">
                    {{-- Ansehen --}}
                    <a href="{{ route('rechnung.show', $r->id) }}" 
                       class="btn btn-sm btn-outline-secondary" title="Ansehen">
                      <i class="bi bi-eye"></i>
                    </a>

                    {{-- Bearbeiten (nur Entwurf) --}}
                    @if($r->ist_editierbar)
                      <a href="{{ route('rechnung.edit', $r->id) }}" 
                         class="btn btn-sm btn-outline-primary" title="Bearbeiten">
                        <i class="bi bi-pencil"></i>
                      </a>
                    @endif

                    {{-- Löschen (nur Entwurf) --}}
                    @if($r->ist_editierbar)
                      <form action="{{ route('rechnung.destroy', $r->id) }}" 
                            method="POST" class="d-inline"
                            onsubmit="return confirm('Rechnung {{ $r->nummern }} wirklich löschen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Löschen">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="text-muted small">
          {{ $rechnungen->total() }} Rechnungen gefunden
        </div>
        <div>
          {{ $rechnungen->appends(compact('jahr', 'status', 'gebaeude_id', 'suche'))->links() }}
        </div>
      </div>
    </div>
  @endif

</div>
@endsection