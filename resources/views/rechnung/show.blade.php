{{-- resources/views/rechnung/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
  
  {{-- Header mit Aktionen --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3>
      <i class="bi bi-receipt"></i> Rechnung {{ $rechnung->rechnungsnummer }}
      <span class="ms-2">{!! $rechnung->status_badge !!}</span>
      {{-- ⭐ NEU: Zahlungsbedingungen Badge --}}
      @if($rechnung->zahlungsbedingungen)
        <span class="ms-2">{!! $rechnung->zahlungsbedingungen_badge !!}</span>
      @endif
    </h3>
    
    <div class="btn-group">
      <a href="{{ route('rechnung.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Zurück
      </a>
      
      @if($rechnung->ist_editierbar)
        <a href="{{ route('rechnung.edit', $rechnung->id) }}" class="btn btn-outline-primary">
          <i class="bi bi-pencil"></i> Bearbeiten
        </a>
      @endif
      
      <a href="{{ route('rechnung.pdf', $rechnung->id) }}" class="btn btn-outline-danger" target="_blank">
        <i class="bi bi-file-pdf"></i> PDF
      </a>
      
      @if($rechnung->fattura_profile_id)
        <a href="{{ route('rechnung.xml', $rechnung->id) }}" class="btn btn-outline-success">
          <i class="bi bi-file-earmark-code"></i> FatturaPA XML
        </a>
      @endif

      {{-- ⭐ NEU: Aktions-Buttons --}}
      @if($rechnung->status === 'draft')
        <button type="button" 
                id="btn-send"
                class="btn btn-info"
                data-send-url="{{ route('rechnung.send', $rechnung->id) }}">
          <i class="bi bi-send"></i> Versenden
        </button>
      @endif

      @if(!$rechnung->istAlsBezahltMarkiert() && $rechnung->status !== 'paid')
        <button type="button" 
                id="btn-mark-bezahlt"
                class="btn btn-success"
                data-mark-url="{{ route('rechnung.mark-bezahlt', $rechnung->id) }}">
          <i class="bi bi-check-circle"></i> Als bezahlt markieren
        </button>
      @endif

      @if($rechnung->status !== 'cancelled')
        <button type="button" 
                id="btn-cancel"
                class="btn btn-outline-danger"
                data-cancel-url="{{ route('rechnung.cancel', $rechnung->id) }}">
          <i class="bi bi-x-circle"></i> Stornieren
        </button>
      @endif
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

  {{-- ⭐ NEU: Fälligkeits-Warnung (wenn überfällig) --}}
  @if($rechnung->istUeberfaellig())
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <strong>Achtung!</strong> Diese Rechnung ist seit {{ abs($rechnung->tage_bis_faelligkeit) }} Tagen überfällig!
      @if($rechnung->faelligkeitsdatum)
        <br><small>Fällig war am: {{ $rechnung->faelligkeitsdatum->format('d.m.Y') }}</small>
      @endif
    </div>
  @endif

  {{-- ⭐ NEU: Zahlungsinformationen (prominente Box) --}}
  @if($rechnung->zahlungsbedingungen)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border-{{ $rechnung->istUeberfaellig() ? 'danger' : ($rechnung->istAlsBezahltMarkiert() ? 'success' : 'warning') }}">
        <div class="card-header bg-{{ $rechnung->istUeberfaellig() ? 'danger' : ($rechnung->istAlsBezahltMarkiert() ? 'success' : 'warning') }} text-white">
          <h6 class="mb-0">
            <i class="bi bi-{{ $rechnung->istAlsBezahltMarkiert() ? 'check-circle' : 'calendar-check' }}"></i> 
            Zahlungsinformationen
          </h6>
        </div>
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-md-8">
              <div class="row g-3">
                <div class="col-md-4">
                  <strong>Zahlungsbedingungen:</strong><br>
                  {!! $rechnung->zahlungsbedingungen_badge !!}
                </div>
                
                @if($rechnung->zahlungsziel)
                <div class="col-md-4">
                  <strong>Zahlungsziel:</strong><br>
                  {{ $rechnung->zahlungsziel->format('d.m.Y') }}
                  @if(!$rechnung->istAlsBezahltMarkiert())
                    <br><small class="text-muted">({{ $rechnung->tage_bis_faelligkeit > 0 ? 'noch ' . $rechnung->tage_bis_faelligkeit . ' Tage' : abs($rechnung->tage_bis_faelligkeit) . ' Tage überfällig' }})</small>
                  @endif
                </div>
                @endif

                @if($rechnung->bezahlt_am)
                <div class="col-md-4">
                  <strong>Bezahlt am:</strong><br>
                  {{ $rechnung->bezahlt_am->format('d.m.Y') }}
                  <br><span class="badge bg-success">✓ Bezahlt</span>
                </div>
                @endif
              </div>
            </div>
            <div class="col-md-4 text-md-end">
              {!! $rechnung->faelligkeits_status_badge !!}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Vorschau-Tab wiederverwenden --}}
  @include('rechnung.partials._vorschau')

  {{-- Zusätzliche Informationen --}}
  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-light">
          <h6 class="mb-0"><i class="bi bi-info-circle"></i> Metadaten</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <strong>Erstellt:</strong><br>
              {{ $rechnung->created_at?->format('d.m.Y H:i') ?? '-' }}
            </div>
            <div class="col-md-3">
              <strong>Zuletzt geändert:</strong><br>
              {{ $rechnung->updated_at?->format('d.m.Y H:i') ?? '-' }}
            </div>
            @if($rechnung->pdf_pfad)
              <div class="col-md-3">
                <strong>PDF-Pfad:</strong><br>
                <code class="small">{{ $rechnung->pdf_pfad }}</code>
              </div>
            @endif
            @if($rechnung->xml_pfad)
              <div class="col-md-3">
                <strong>XML-Pfad:</strong><br>
                <code class="small">{{ $rechnung->xml_pfad }}</code>
              </div>
            @endif
            @if($rechnung->externe_referenz)
              <div class="col-md-3">
                <strong>Externe Referenz:</strong><br>
                {{ $rechnung->externe_referenz }}
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Verknüpftes Gebäude --}}
  @if($rechnung->gebaeude)
    <div class="row mt-3">
      <div class="col-12">
        <div class="card border-info">
          <div class="card-header bg-info text-white">
            <h6 class="mb-0"><i class="bi bi-link-45deg"></i> Verknüpftes Gebäude</h6>
          </div>
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <strong>{{ $rechnung->gebaeude->codex }}</strong> - {{ $rechnung->gebaeude->gebaeude_name }}
                <br>
                <small class="text-muted">
                  {{ $rechnung->gebaeude->strasse }} {{ $rechnung->gebaeude->hausnummer }}, 
                  {{ $rechnung->gebaeude->plz }} {{ $rechnung->gebaeude->wohnort }}
                </small>
              </div>
              <div class="col-md-4 text-end">
                <a href="{{ route('gebaeude.edit', $rechnung->gebaeude_id) }}" 
                   class="btn btn-sm btn-outline-info">
                  <i class="bi bi-building"></i> Gebäude öffnen
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif

</div>

{{-- ⭐ NEU: JavaScript für Aktions-Buttons (OHNE verschachtelte Forms!) --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';

    // Als bezahlt markieren
    const btnMarkBezahlt = document.getElementById('btn-mark-bezahlt');
    if (btnMarkBezahlt) {
        btnMarkBezahlt.addEventListener('click', function() {
            if (!confirm('Rechnung als bezahlt markieren?')) return;
            
            const url = this.getAttribute('data-mark-url');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Fehler beim Markieren als bezahlt');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Netzwerkfehler');
            });
        });
    }

    // Versenden
    const btnSend = document.getElementById('btn-send');
    if (btnSend) {
        btnSend.addEventListener('click', function() {
            if (!confirm('Rechnung versenden?')) return;
            
            const url = this.getAttribute('data-send-url');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Fehler beim Versenden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Netzwerkfehler');
            });
        });
    }

    // Stornieren
    const btnCancel = document.getElementById('btn-cancel');
    if (btnCancel) {
        btnCancel.addEventListener('click', function() {
            if (!confirm('Rechnung wirklich stornieren? Dieser Vorgang kann nicht rückgängig gemacht werden!')) return;
            
            const url = this.getAttribute('data-cancel-url');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Fehler beim Stornieren');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Netzwerkfehler');
            });
        });
    }
});
</script>
@endsection