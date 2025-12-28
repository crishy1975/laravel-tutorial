@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="bi bi-file-text"></i> Arbeitsberichte
        </h4>
        <a href="{{ route('gebaeude.index') }}" class="btn btn-primary">
            <i class="bi bi-plus"></i> Neuer Bericht
        </a>
    </div>

    <!-- Statistiken -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-warning bg-opacity-10 border-warning">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['offen'] }}</h3>
                    <small class="text-muted">Offen</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success bg-opacity-10 border-success">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['unterschrieben'] }}</h3>
                    <small class="text-muted">Unterschrieben</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger bg-opacity-10 border-danger">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['abgelaufen'] }}</h3>
                    <small class="text-muted">Abgelaufen</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('arbeitsbericht.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" 
                           class="form-control" 
                           name="suche" 
                           placeholder="Suche nach Name/Adresse..."
                           value="{{ request('suche') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Alle Status</option>
                        <option value="erstellt" {{ request('status') == 'erstellt' ? 'selected' : '' }}>Erstellt</option>
                        <option value="gesendet" {{ request('status') == 'gesendet' ? 'selected' : '' }}>Gesendet</option>
                        <option value="unterschrieben" {{ request('status') == 'unterschrieben' ? 'selected' : '' }}>Unterschrieben</option>
                        <option value="abgelaufen" {{ request('status') == 'abgelaufen' ? 'selected' : '' }}>Abgelaufen</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search"></i> Suchen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabelle -->
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Datum</th>
                        <th>Kunde / Adresse</th>
                        <th>Nächste Fälligkeit</th>
                        <th>Status</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($berichte as $bericht)
                    <tr>
                        <td>
                            <strong>{{ $bericht->arbeitsdatum->format('d.m.Y') }}</strong>
                        </td>
                        <td>
                            <div>{{ $bericht->adresse_name }}</div>
                            <small class="text-muted">
                                {{ $bericht->adresse_plz }} {{ $bericht->adresse_wohnort }}
                            </small>
                        </td>
                        <td>
                            @if($bericht->naechste_faelligkeit)
                                {{ $bericht->naechste_faelligkeit->format('d.m.Y') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @switch($bericht->status)
                                @case('unterschrieben')
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Unterschrieben
                                    </span>
                                    @break
                                @case('gesendet')
                                    <span class="badge bg-info">
                                        <i class="bi bi-send"></i> Gesendet
                                    </span>
                                    @break
                                @case('abgelaufen')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i> Abgelaufen
                                    </span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-file-text"></i> Erstellt
                                    </span>
                            @endswitch
                            
                            @if($bericht->abgerufen_am && !$bericht->istUnterschrieben())
                                <span class="badge bg-light text-dark ms-1" title="Abgerufen am {{ $bericht->abgerufen_am->format('d.m.Y H:i') }}">
                                    <i class="bi bi-eye"></i>
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('arbeitsbericht.show', $bericht) }}" 
                                   class="btn btn-outline-primary" 
                                   title="Anzeigen">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('arbeitsbericht.pdf', $bericht) }}" 
                                   class="btn btn-outline-secondary" 
                                   title="PDF">
                                    <i class="bi bi-file-pdf"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-outline-secondary" 
                                        onclick="copyToClipboard('{{ $bericht->public_link }}')"
                                        title="Link kopieren">
                                    <i class="bi bi-link"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-4 d-block mb-3"></i>
                            Keine Arbeitsberichte gefunden
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($berichte->hasPages())
        <div class="card-footer">
            {{ $berichte->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Kurzes Feedback
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="toast show bg-success text-white">
                <div class="toast-body">
                    <i class="bi bi-check-circle"></i> Link kopiert!
                </div>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    });
}
</script>
@endsection
