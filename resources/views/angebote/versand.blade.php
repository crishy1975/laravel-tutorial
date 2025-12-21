{{-- resources/views/angebote/versand.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">

    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-envelope"></i> 
                Angebot {{ $angebot->angebotsnummer }} versenden
            </h4>
            <small class="text-muted">{{ $angebot->titel }}</small>
        </div>
        <a href="{{ route('angebote.edit', $angebot) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('angebote.versenden', $angebot) }}">
                @csrf

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-envelope"></i> E-Mail Versand / Invio E-Mail
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Empfänger E-Mail <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="{{ old('email', $email) }}" required>
                            @if(!$email)
                                <div class="form-text text-warning">
                                    <i class="bi bi-exclamation-triangle"></i> Keine E-Mail-Adresse hinterlegt!
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Betreff <span class="text-danger">*</span></label>
                            <input type="text" name="betreff" class="form-control" 
                                   value="{{ old('betreff', $betreff) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nachricht <span class="text-danger">*</span></label>
                            <textarea name="text" class="form-control" rows="15" required>{{ old('text', $text) }}</textarea>
                        </div>

                        <div class="alert alert-info mb-0">
                            <i class="bi bi-paperclip"></i>
                            <strong>Anhang:</strong> Angebot_{{ $angebot->angebotsnummer }}.pdf
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send"></i> Jetzt versenden
                    </button>
                    <a href="{{ route('angebote.pdf', ['angebot' => $angebot, 'preview' => 1]) }}" 
                       class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye"></i> PDF Vorschau
                    </a>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            {{-- Angebot-Info --}}
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="bi bi-file-earmark-text"></i> Angebot
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">Nr.:</td>
                            <td class="fw-bold">{{ $angebot->angebotsnummer }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Datum:</td>
                            <td>{{ $angebot->datum->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Gültig bis:</td>
                            <td>
                                @if($angebot->gueltig_bis)
                                    {{ $angebot->gueltig_bis->format('d.m.Y') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Betrag:</td>
                            <td class="fw-bold">{{ $angebot->brutto_formatiert }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status:</td>
                            <td>{!! $angebot->status_badge !!}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Empfänger-Info --}}
            <div class="card">
                <div class="card-header bg-light">
                    <i class="bi bi-person"></i> Empfänger
                </div>
                <div class="card-body">
                    <strong>{{ $angebot->empfaenger_name }}</strong><br>
                    {{ $angebot->empfaenger_strasse }} {{ $angebot->empfaenger_hausnummer }}<br>
                    {{ $angebot->empfaenger_plz }} {{ $angebot->empfaenger_ort }}
                    @if($angebot->empfaenger_email)
                        <hr class="my-2">
                        <i class="bi bi-envelope"></i> {{ $angebot->empfaenger_email }}
                    @endif
                </div>
            </div>

            {{-- Vorheriger Versand --}}
            @if($angebot->versendet_am)
                <div class="card mt-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <i class="bi bi-exclamation-triangle"></i> Bereits versendet!
                    </div>
                    <div class="card-body">
                        <strong>{{ $angebot->versendet_am->format('d.m.Y H:i') }}</strong><br>
                        an {{ $angebot->versendet_an_email }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
