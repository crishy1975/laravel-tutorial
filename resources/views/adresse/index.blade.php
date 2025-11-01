@extends('layouts.app')

@section('content')
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-geo-alt"></i> Adressen</h3>
    <div class="d-flex gap-2">
      <a href="{{ route('adresse.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Neu
      </a>
    </div>
  </div>

  @if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Filterleiste: nur Name --}}
  <form method="GET" action="{{ route('adresse.index') }}" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label mb-1">Name enthält</label>
        <input type="text" name="name" value="{{ $name ?? '' }}" class="form-control" placeholder="z. B. Resch">
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100" type="submit">
          <i class="bi bi-search"></i> Suchen
        </button>
      </div>
      @if(($name ?? '') !== '')
      <div class="col-md-2">
        <a href="{{ route('adresse.index') }}" class="btn btn-outline-dark w-100">
          <i class="bi bi-x-circle"></i> Zurücksetzen
        </a>
      </div>
      @endif
    </div>
  </form>

  @if($adressen->isEmpty())
  <div class="alert alert-info">Keine Adressen gefunden.</div>
  @else
  {{-- Bulk-Delete-Form um die Tabelle legen --}}
  <form id="bulkForm" method="POST" action="{{ route('adresse.bulkDestroy') }}">
    @csrf

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:36px;">
              <input type="checkbox" id="checkAll">
            </th>
            <th>Name</th>
            <th>Ort</th>
            <th>Telefon</th>
            <th>E-Mail</th>
            <th class="text-end">Aktionen</th>
          </tr>
        </thead>
        <tbody>
          @foreach($adressen as $adr)
          <tr>
            <td>
              <input type="checkbox" name="ids[]" value="{{ $adr->id }}" class="row-check">
            </td>
            <td>{{ $adr->name }}</td>
            <td>{{ $adr->plz }} {{ $adr->wohnort }} @if($adr->provinz) ({{ $adr->provinz }}) @endif</td>
            <td>{{ $adr->telefon }}</td>
            <td>{{ $adr->email }}</td>
            <td class="text-end">
              <a href="{{ route('adresse.show', ['id'=>$adr->id]) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-eye"></i>
              </a>
              <a href="{{ route('adresse.edit', ['id'=>$adr->id]) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i>
              </a>

              {{-- Einzel-Löschen: Button, der ein externes Formular referenziert --}}
              <button type="submit"
                class="btn btn-sm btn-outline-danger"
                form="del-{{ $adr->id }}"
                onclick="return confirm('Eintrag löschen?')">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2">
      <div>
        <button type="submit" class="btn btn-outline-danger"
          onclick="return confirm('Markierte Adressen wirklich löschen?')">
          <i class="bi bi-trash"></i> Markierte löschen
        </button>
      </div>
      <div>
        {{-- Pagination mit Filter beibehalten --}}
        {{ $adressen->appends(['name' => $name])->links() }}
      </div>
    </div>
  </form>

  {{-- Externe Formulare für Einzel-Löschen (nicht im bulkForm, daher kein Verschachteln) --}}
  @foreach($adressen as $adr)
  <form id="del-{{ $adr->id }}" method="POST" action="{{ route('adresse.destroy', ['id'=>$adr->id]) }}" class="d-none">
    @csrf
    @method('DELETE')
  </form>
  @endforeach
  @endif
</div>

{{-- Minimal-JS: Alle auswählen --}}
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const boxes = document.querySelectorAll('.row-check');
    if (checkAll) {
      checkAll.addEventListener('change', function() {
        boxes.forEach(b => b.checked = checkAll.checked);
      });
    }
  });
</script>
@endsection