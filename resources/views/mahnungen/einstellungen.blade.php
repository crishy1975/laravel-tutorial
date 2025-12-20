{{-- resources/views/mahnungen/einstellungen.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-sliders"></i> Mahnwesen Einstellungen</h4>
            <small class="text-muted">Konfiguration für Zahlungsfristen und Mahnintervalle</small>
        </div>
        <a href="{{ route('mahnungen.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Allgemeine Einstellungen --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Allgemeine Einstellungen</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('mahnungen.einstellungen.speichern') }}">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="zahlungsfrist_tage" class="form-label">
                                <i class="bi bi-calendar-check"></i>
                                Zahlungsfrist
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" 
                                       id="zahlungsfrist_tage" 
                                       name="zahlungsfrist_tage" 
                                       value="{{ $einstellungen['zahlungsfrist_tage'] ?? 30 }}"
                                       min="1" max="365" required>
                                <span class="input-group-text">Tage</span>
                            </div>
                            <small class="text-muted">
                                Nach diesem Zeitraum gilt eine Rechnung als überfällig.
                            </small>
                        </div>

                        <div class="mb-4">
                            <label for="wartezeit_zwischen_mahnungen" class="form-label">
                                <i class="bi bi-clock-history"></i>
                                Wartezeit zwischen Mahnungen
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" 
                                       id="wartezeit_zwischen_mahnungen" 
                                       name="wartezeit_zwischen_mahnungen" 
                                       value="{{ $einstellungen['wartezeit_zwischen_mahnungen'] ?? 10 }}"
                                       min="1" max="365" required>
                                <span class="input-group-text">Tage</span>
                            </div>
                            <small class="text-muted">
                                Mindestabstand zwischen zwei Mahnungen für dieselbe Rechnung.
                            </small>
                        </div>

                        <div class="mb-4">
                            <label for="min_tage_ueberfaellig" class="form-label">
                                <i class="bi bi-hourglass-split"></i>
                                Mindest-Überfälligkeit für erste Mahnung
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" 
                                       id="min_tage_ueberfaellig" 
                                       name="min_tage_ueberfaellig" 
                                       value="{{ $einstellungen['min_tage_ueberfaellig'] ?? 0 }}"
                                       min="0" max="365" required>
                                <span class="input-group-text">Tage</span>
                            </div>
                            <small class="text-muted">
                                Zusätzliche Karenzzeit nach Fälligkeit bevor gemahnt wird. 0 = sofort.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Einstellungen speichern
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info-Box --}}
        <div class="col-md-6">
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> So funktioniert's</h5>
                </div>
                <div class="card-body">
                    <h6>Beispiel mit aktuellen Einstellungen:</h6>
                    
                    @php
                        $zahlungsfrist = $einstellungen['zahlungsfrist_tage'] ?? 30;
                        $wartezeit = $einstellungen['wartezeit_zwischen_mahnungen'] ?? 10;
                        $minTage = $einstellungen['min_tage_ueberfaellig'] ?? 0;
                    @endphp
                    
                    <ol class="mb-0">
                        <li class="mb-2">
                            <strong>Rechnung wird erstellt</strong><br>
                            <small class="text-muted">Tag 0</small>
                        </li>
                        <li class="mb-2">
                            <strong>Zahlungsfrist läuft ab</strong><br>
                            <small class="text-muted">Tag {{ $zahlungsfrist }}</small>
                        </li>
                        @if($minTage > 0)
                        <li class="mb-2">
                            <strong>Karenzzeit</strong><br>
                            <small class="text-muted">Tag {{ $zahlungsfrist }} bis {{ $zahlungsfrist + $minTage }}</small>
                        </li>
                        @endif
                        <li class="mb-2">
                            <strong>Erste Mahnung möglich</strong><br>
                            <small class="text-muted">Ab Tag {{ $zahlungsfrist + $minTage }}</small>
                        </li>
                        <li class="mb-2">
                            <strong>Zweite Mahnung möglich</strong><br>
                            <small class="text-muted">Ab Tag {{ $zahlungsfrist + $minTage + $wartezeit }} ({{ $wartezeit }} Tage Wartezeit)</small>
                        </li>
                        <li class="mb-0">
                            <strong>Dritte Mahnung möglich</strong><br>
                            <small class="text-muted">Ab Tag {{ $zahlungsfrist + $minTage + $wartezeit * 2 }} (weitere {{ $wartezeit }} Tage)</small>
                        </li>
                    </ol>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list-ol"></i> Mahnstufen</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Die Mahnstufen (Texte, Spesen, etc.) werden separat konfiguriert:
                    </p>
                    <a href="{{ route('mahnungen.stufen') }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil-square"></i> Mahnstufen bearbeiten
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
