@extends('layouts.app')

@section('title', 'Textvorschläge')

@section('content')
<div class="container-fluid py-2 py-md-4">
    
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">
                <i class="bi bi-chat-quote text-primary"></i>
                Textvorschläge
            </h1>
            <p class="text-muted small mb-0">
                Vorlagen für SMS, WhatsApp, Angebote
            </p>
        </div>
        <a href="{{ route('textvorschlaege.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i>
            <span class="d-none d-sm-inline ms-1">Neu</span>
        </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistik --}}
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="card border-primary h-100">
                <div class="card-body text-center py-2">
                    <h3 class="fw-bold text-primary mb-0">{{ $stats['gesamt'] }}</h3>
                    <small class="text-muted">Gesamt</small>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-success h-100">
                <div class="card-body text-center py-2">
                    <h3 class="fw-bold text-success mb-0">{{ $stats['aktiv'] }}</h3>
                    <small class="text-muted">Aktiv</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('textvorschlaege.index') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-4">
                    <label class="form-label small mb-1">Kategorie</label>
                    <select name="kategorie" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Alle Kategorien</option>
                        @foreach($kategorien as $key => $name)
                            <option value="{{ $key }}" @selected(request('kategorie') == $key)>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Alle</option>
                        <option value="aktiv" @selected(request('status') == 'aktiv')>Aktiv</option>
                        <option value="inaktiv" @selected(request('status') == 'inaktiv')>Inaktiv</option>
                    </select>
                </div>
                <div class="col-8 col-md-3">
                    <label class="form-label small mb-1">Suche</label>
                    <input type="text" name="suche" class="form-control form-control-sm" 
                           value="{{ request('suche') }}" placeholder="Titel oder Text...">
                </div>
                <div class="col-4 col-md-2">
                    <a href="{{ route('textvorschlaege.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabelle --}}
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 180px;">Kategorie</th>
                        <th style="width: 150px;">Titel</th>
                        <th>Text (Vorschau)</th>
                        <th style="width: 80px;" class="text-center">Status</th>
                        <th style="width: 100px;" class="text-end">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vorschlaege as $v)
                        <tr class="{{ !$v->aktiv ? 'table-secondary' : '' }}">
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ $v->kategorie_name }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $v->titel ?: '-' }}</strong>
                            </td>
                            <td>
                                <span class="small" title="{{ $v->text }}">
                                    {{ Str::limit($v->text, 80) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('textvorschlaege.toggle', $v) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $v->aktiv ? 'btn-success' : 'btn-outline-secondary' }}" 
                                            title="{{ $v->aktiv ? 'Deaktivieren' : 'Aktivieren' }}">
                                        <i class="bi {{ $v->aktiv ? 'bi-check-lg' : 'bi-x' }}"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('textvorschlaege.edit', $v) }}" class="btn btn-outline-primary" title="Bearbeiten">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('textvorschlaege.destroy', $v) }}" 
                                          class="d-inline" onsubmit="return confirm('Wirklich löschen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Löschen">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                Keine Textvorschläge vorhanden.
                                <br>
                                <a href="{{ route('textvorschlaege.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus-lg"></i> Ersten Vorschlag erstellen
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($vorschlaege->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $vorschlaege->links() }}
        </div>
    @endif

</div>
@endsection
