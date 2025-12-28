@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <!-- Success Alert nach Erstellung -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <!-- Header Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4 class="mb-1">
                                Arbeitsbericht #{{ $arbeitsbericht->id }}
                            </h4>
                            <p class="text-muted mb-0">
                                <i class="bi bi-calendar"></i> {{ $arbeitsbericht->arbeitsdatum->format('d.m.Y') }}
                                @if($arbeitsbericht->gebaeude)
                                    | <i class="bi bi-building"></i> {{ $arbeitsbericht->gebaeude->gebaeude_name }}
                                @endif
                            </p>
                        </div>
                        <span class="badge bg-success fs-6">
                            <i class="bi bi-check-circle"></i> Unterschrieben
                        </span>
                    </div>
                </div>
            </div>

            <!-- Adresse -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-geo-alt"></i> Adresse
                </div>
                <div class="card-body">
                    <pre class="mb-0" style="font-family: inherit; white-space: pre-line;">{{ $arbeitsbericht->volle_adresse }}</pre>
                </div>
            </div>

            <!-- Positionen -->
            @if(!empty($arbeitsbericht->positionen))
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-list-check"></i> Positionen
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Bezeichnung</th>
                                <th class="text-end">Anzahl</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($arbeitsbericht->positionen as $position)
                            <tr>
                                <td>{{ $position['bezeichnung'] }}</td>
                                <td class="text-end">{{ $position['anzahl'] }} {{ $position['einheit'] ?? '' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Bemerkung -->
            @if($arbeitsbericht->bemerkung)
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-chat-text"></i> Bemerkung
                </div>
                <div class="card-body">
                    <p class="mb-0 fst-italic">{{ $arbeitsbericht->bemerkung }}</p>
                </div>
            </div>
            @endif

            <!-- Unterschrift -->
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-pen"></i> Unterschrift Kunde
                </div>
                <div class="card-body text-center">
                    @if($arbeitsbericht->unterschrift_kunde)
                    <img src="{{ $arbeitsbericht->unterschrift_kunde }}" 
                         alt="Unterschrift" 
                         style="max-height: 100px; border: 1px solid #eee; border-radius: 8px; padding: 10px; background: #fafafa;">
                    @endif
                    <div class="mt-3">
                        <strong>{{ $arbeitsbericht->unterschrift_name }}</strong><br>
                        <small class="text-muted">
                            {{ $arbeitsbericht->unterschrieben_am->format('d.m.Y H:i') }} Uhr
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- WICHTIG: Link senden -->
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-send"></i> Link an Kunden senden
                </div>
                <div class="card-body">
                    @if($arbeitsbericht->status === 'gesendet')
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-check"></i> Link wurde bereits gesendet
                        </div>
                    @endif

                    <button type="button" 
                            class="btn btn-primary btn-lg w-100 mb-3" 
                            data-bs-toggle="modal" 
                            data-bs-target="#sendenModal">
                        <i class="bi bi-send"></i> Jetzt senden
                    </button>

                    <div class="small text-muted">
                        Der Kunde erhält einen Link zum PDF-Download.<br>
                        Gültig bis: <strong>{{ $arbeitsbericht->gueltig_bis->format('d.m.Y') }}</strong>
                    </div>
                </div>
            </div>

            <!-- Link kopieren -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-link-45deg"></i> Download-Link
                </div>
                <div class="card-body">
                    <div class="input-group mb-2">
                        <input type="text" 
                               class="form-control form-control-sm" 
                               value="{{ $arbeitsbericht->public_link }}" 
                               id="publicLink" 
                               readonly>
                        <button class="btn btn-outline-secondary btn-sm" 
                                type="button" 
                                onclick="copyLink()">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>

                    @if($arbeitsbericht->abgerufen_am)
                    <div class="small text-success">
                        <i class="bi bi-eye"></i> 
                        Abgerufen am {{ $arbeitsbericht->abgerufen_am->format('d.m.Y H:i') }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- Nächste Fälligkeit -->
            @if($arbeitsbericht->naechste_faelligkeit)
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-calendar-event"></i> Nächste Reinigung
                </div>
                <div class="card-body">
                    <h4 class="text-primary mb-0">
                        {{ $arbeitsbericht->naechste_faelligkeit->format('d.m.Y') }}
                    </h4>
                </div>
            </div>
            @endif

            <!-- Weitere Aktionen -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-gear"></i> Weitere Aktionen
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('arbeitsbericht.pdf', $arbeitsbericht) }}" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-file-pdf"></i> PDF herunterladen
                    </a>

                    <a href="{{ route('arbeitsbericht.index') }}" 
                       class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Zurück zur Liste
                    </a>

                    <form action="{{ route('arbeitsbericht.destroy', $arbeitsbericht) }}" 
                          method="POST" 
                          onsubmit="return confirm('Wirklich löschen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash"></i> Löschen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Senden Modal -->
<div class="modal fade" id="sendenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('arbeitsbericht.senden', $arbeitsbericht) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-send"></i> Link an Kunden senden
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Kanal -->
                    <div class="mb-4">
                        <label class="form-label">Senden per</label>
                        <div class="row g-2">
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="kanal" id="kanalWhatsapp" value="whatsapp" checked>
                                <label class="btn btn-outline-success w-100 py-3" for="kanalWhatsapp">
                                    <i class="bi bi-whatsapp fs-4 d-block mb-1"></i>
                                    WhatsApp
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="kanal" id="kanalSms" value="sms">
                                <label class="btn btn-outline-primary w-100 py-3" for="kanalSms">
                                    <i class="bi bi-chat-dots fs-4 d-block mb-1"></i>
                                    SMS
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="kanal" id="kanalEmail" value="email">
                                <label class="btn btn-outline-secondary w-100 py-3" for="kanalEmail">
                                    <i class="bi bi-envelope fs-4 d-block mb-1"></i>
                                    E-Mail
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Empfänger -->
                    <div class="mb-3">
                        <label for="empfaenger" class="form-label">Empfänger *</label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="empfaenger" 
                               name="empfaenger" 
                               placeholder="+39 333 1234567"
                               value="{{ $arbeitsbericht->gebaeude?->handy ?? $arbeitsbericht->gebaeude?->telefon ?? '' }}"
                               required>
                        <div class="form-text">Telefonnummer oder E-Mail-Adresse</div>
                    </div>

                    <!-- Nachricht (optional) -->
                    <div class="mb-3">
                        <label for="nachricht" class="form-label">Nachricht (optional)</label>
                        <textarea class="form-control" 
                                  id="nachricht" 
                                  name="nachricht" 
                                  rows="3"
                                  placeholder="Standard: Ihr Arbeitsbericht steht zum Download bereit..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-send"></i> Senden
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyLink() {
    const input = document.getElementById('publicLink');
    input.select();
    navigator.clipboard.writeText(input.value);
    
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check"></i>';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-secondary');
    
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
    }, 2000);
}
</script>
@endsection
