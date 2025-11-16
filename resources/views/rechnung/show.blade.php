{{-- resources/views/rechnung/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
  
  {{-- Header mit Aktionen --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3>
      <i class="bi bi-receipt"></i> Rechnung {{ $rechnung->nummern }}
      <span class="ms-2">{!! $rechnung->status_badge !!}</span>
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
    </div>
  </div>

  {{-- Flash Messages --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
@endsection