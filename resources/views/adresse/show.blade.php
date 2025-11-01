{{-- resources/views/adressen/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3>Adresse: {{ $adresse->name }}</h3>
  <p>{{ $adresse->strasse }} {{ $adresse->hausnummer }}</p>
  <p>{{ $adresse->plz }} {{ $adresse->wohnort }} ({{ $adresse->provinz }})</p>
  <p>Telefon: {{ $adresse->telefon }} | E-Mail: {{ $adresse->email }}</p>

  </div>
@endsection
