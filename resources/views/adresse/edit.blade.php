{{-- resources/views/adresse/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3>Adresse bearbeiten</h3>
  <hr>

  {{-- 🔁 Validierungsfehler --}}
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('adresse.update', $adresse->id) }}">
    @csrf
    @method('PUT')

    @php
      /*
        $returnToVal wird EINMAL sauber bestimmt und dann überall verwendet:
        - old('returnTo') holt den Wert nach einer Validierungs-Umleitung zurück
        - $returnTo (vom Controller) falls vorhanden
        - request()->query('returnTo') als Fallback direkt aus der URL
      */
      $returnToVal = old('returnTo', ($returnTo ?? request()->query('returnTo')));
    @endphp

    {{-- ✅ Hidden-Feld nur setzen, wenn Wert vorhanden --}}
    @if(!empty($returnToVal))
      <input type="hidden" name="returnTo" value="{{ $returnToVal }}">
    @endif

    {{-- 🧩 Formularfelder --}}
    @include('adresse._form', ['adresse' => $adresse])

    <div class="mt-4 d-flex gap-2">
      @php
        // 🔙 Abbrechen: wenn returnTo existiert, dorthin; sonst Detailseite
        $backHref = !empty($returnToVal)
                    ? $returnToVal
                    : route('adresse.show', $adresse->id);
      @endphp

      <a href="{{ $backHref }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Abbrechen
      </a>

      <button class="btn btn-primary">
        <i class="bi bi-check2-circle"></i> Speichern
      </button>
    </div>
  </form>
</div>
@endsection
