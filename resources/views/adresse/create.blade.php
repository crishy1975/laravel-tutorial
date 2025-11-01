{{-- resources/views/adressen/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">

  {{-- 🔹 VIES-Check: MwSt-Nummer prüfen & Formular serverseitig vorfüllen --}}
  <form method="POST" action="{{ route('tools.viesLookup') }}" class="row g-2 mb-3">
    @csrf
    <input type="hidden" name="country" value="IT">

    <div class="col-md-4">
      <input name="vat" class="form-control" placeholder="Partita IVA"
             value="{{ session('vies_mwst', old('mwst_nummer', $adresse->mwst_nummer)) }}">
    </div>
    <div class="col-md-2">
      <button class="btn btn-outline-secondary w-100">VIES abfragen</button>
    </div>
  </form>

  {{-- 🔹 Ergebnis der VIES-Abfrage --}}
  @if(session('vies_valid') !== null)
    @if(session('vies_valid'))
      <div class="alert alert-success p-2">
        <strong>Gültig.</strong>
        {{ session('vies_name') }}
        @if(session('vies_address')) — {!! nl2br(e(session('vies_address'))) !!} @endif
      </div>
    @else
      <div class="alert alert-warning p-2">USt-ID ungültig oder keine Details verfügbar.</div>
    @endif
  @endif

  @error('vies')
    <div class="alert alert-danger">{{ $message }}</div>
  @enderror

  <h3 class="mt-3"><i class="bi bi-person-plus"></i> Neue Adresse</h3>
  <hr>

  {{-- 🔹 Address-Form --}}
  <form method="POST" action="{{ route('adresse.store') }}">
    @csrf

    {{-- ✅ Rücksprungziel mitschicken (nur hier relevant) --}}
    @if(!empty(request()->query('returnTo')))
      <input type="hidden" name="returnTo" value="{{ request()->query('returnTo') }}">
    @endif

    @include('adresse._form', ['adresse' => $adresse])

    <div class="mt-4 d-flex gap-2">
      <a href="{{ route('adresse.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Zurück
      </a>
      <button class="btn btn-primary">
        <i class="bi bi-check2-circle"></i> Speichern
      </button>
    </div>
  </form>

</div>
@endsection
