{{-- resources/views/gebaeude/partials/_artikel.blade.php --}}
{{-- ⭐ FINALE VERSION mit:
   1. basis_jahr = AKTUELLES Jahr (nicht nächstes!)
   2. Kumulative Erhöhungen seit basis_jahr
   3. basis_jahr als Tooltip (nicht sichtbar)
--}}

@php
  /** @var \App\Models\Gebaeude $gebaeude */
  $hasId = isset($gebaeude) && $gebaeude?->exists;
  $aktuellesJahr = now()->year;
  $naechstesJahr = $aktuellesJahr + 1;

  if ($hasId) {
      // Aufschlag-Info für Banner
      $aufschlagNaechstesJahr = $gebaeude->getAufschlagProzent($naechstesJahr);
      $hatIndividuell = $gebaeude->hatIndividuellenAufschlag();
      
      // Summe berechnen (mit kumulativer Erhöhung pro Artikel)
      $serverSumAktiv = 0.0;
      foreach (($gebaeude->artikel ?? []) as $artikel) {
          if (!$artikel->aktiv) continue;
          
          $basisJahr = $artikel->basis_jahr ?? $aktuellesJahr;
          $basisPreis = $artikel->basis_preis ?? $artikel->einzelpreis;
          
          // ⭐ KUMULATIVE Erhöhung seit basis_jahr
          $preis = $gebaeude->berechnePreisMitKumulativerErhoehung(
              $basisPreis,
              $basisJahr,
              $aktuellesJahr
          );
          
          $serverSumAktiv += (float)$artikel->anzahl * $preis;
      }
  } else {
      $serverSumAktiv = 0.0;
      $aufschlagNaechstesJahr = 0;
      $hatIndividuell = false;
  }

  // Datensätze nur laden, wenn ID vorhanden
  $artikelListe = $hasId ? ($gebaeude->artikel ?? []) : [];
@endphp

<div class="row g-4">

  {{-- Kopf --}}
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-receipt text-muted"></i>
        <span class="fw-semibold">Artikel / Positionen</span>
      </div>
      <div class="d-flex align-items-center gap-4">
        <div class="form-check form-switch m-0">
          <input class="form-check-input" type="checkbox" id="art-only-active" checked {{ $hasId ? '' : 'disabled' }}>
          <label class="form-check-label" for="art-only-active">Nur aktive anzeigen</label>
        </div>
        <div class="text-muted small">
          Summe (aktiv): <span id="art-summe-head">{{ number_format($serverSumAktiv, 2, ',', '.') }}</span> €
        </div>
      </div>
    </div>
    
    {{-- ⭐ Aufschlag-Info-Banner --}}
    @if($hasId && $aufschlagNaechstesJahr != 0)
    <div class="alert alert-info py-2 px-3 mt-2 mb-0 d-flex align-items-center gap-2">
      <i class="bi bi-info-circle"></i>
      <div class="small">
        <strong>Preis-Aufschlag ab {{ $naechstesJahr }}:</strong> 
        @if($aufschlagNaechstesJahr > 0)
          <span class="text-success">+{{ number_format($aufschlagNaechstesJahr, 2, ',', '.') }}%</span>
        @else
          <span class="text-danger">{{ number_format($aufschlagNaechstesJahr, 2, ',', '.') }}%</span>
        @endif
        @if($hatIndividuell)
          <span class="badge bg-warning text-dark ms-1">Individuell</span>
        @else
          <span class="badge bg-primary ms-1">Global</span>
        @endif
        <span class="text-muted">| Neue Preise gelten ab {{ $aktuellesJahr }}</span>
      </div>
    </div>
    @endif
    
    <hr class="mt-2 mb-0">
  </div>

  {{-- Hinweis auf Create-Seite --}}
  @unless($hasId)
    <div class="col-12">
      <div class="alert alert-info py-2 mb-0">
        Bitte Gebäude zuerst speichern – danach können Artikel erfasst und bearbeitet werden.
      </div>
    </div>
  @endunless

  {{-- Tabelle --}}
  <div class="col-12">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="art-table">
        <thead class="table-light">
          <tr>
            <th style="width: 40px;" title="Ziehen für Sortierung"><i class="bi bi-grip-vertical"></i></th>
            <th>Beschreibung</th>
            <th style="width: 120px;">Anzahl</th>
            <th style="width: 140px;">Einzelpreis (€)</th>
            <th style="width: 140px;">Gesamt (€)</th>
            <th style="width: 120px;">Aktiv</th>
            <th class="text-end" style="width: 120px;">Aktionen</th>
          </tr>
        </thead>
        <tbody id="art-tbody">

          {{-- Eingabezeile: Neu --}}
          <tr id="art-new-row" class="table-secondary">
            <td class="text-center">
              <i class="bi bi-plus-circle text-success"></i>
            </td>
            <td>
              <input type="text" class="form-control" id="new-beschreibung" placeholder="Beschreibung" {{ $hasId ? '' : 'disabled' }}>
            </td>
            <td>
              <input type="number" class="form-control text-end" id="new-anzahl" step="0.01" min="0" value="1" {{ $hasId ? '' : 'disabled' }}>
            </td>
            <td>
              <input type="number" class="form-control text-end" id="new-einzelpreis" step="0.01" min="0" value="0.00" 
                     title="Basispreis ab {{ $aktuellesJahr }}" {{ $hasId ? '' : 'disabled' }}>
            </td>
            <td>
              <input type="text" class="form-control text-end" id="new-gesamtpreis" value="0,00" disabled>
            </td>
            <td>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="new-aktiv" {{ $hasId ? 'checked' : 'disabled' }}>
              </div>
            </td>
            <td class="text-end">
              <button type="button" id="btn-add-art" class="btn btn-sm btn-success" {{ $hasId ? '' : 'disabled' }}>
                <i class="bi bi-check2-circle"></i> Hinzufügen
              </button>
            </td>
          </tr>

          {{-- Bestehende Datensätze --}}
          @foreach($artikelListe as $p)
            @php 
              $basisJahr = $p->basis_jahr ?? $aktuellesJahr;
              $basisPreis = $p->basis_preis ?? $p->einzelpreis;
              
              // ⭐ KUMULATIVE Erhöhung berechnen
              $anzeigePreis = $gebaeude->berechnePreisMitKumulativerErhoehung(
                  $basisPreis,
                  $basisJahr,
                  $aktuellesJahr
              );
              
              $rowTotal = (float)$p->anzahl * $anzeigePreis;
              
              // Tooltip-Text erstellen
              $tooltip = "Basispreis: " . number_format($basisPreis, 2, ',', '.') . " € (seit {$basisJahr})";
              if ($anzeigePreis != $basisPreis) {
                  $jahre = $aktuellesJahr - $basisJahr;
                  $tooltip .= " | Erhöht über {$jahre} Jahr(e)";
              }
            @endphp
            <tr data-id="{{ $p->id }}" 
                draggable="true" 
                data-aktiv="{{ $p->aktiv ? '1' : '0' }}"
                data-basis-preis="{{ $basisPreis }}"
                data-basis-jahr="{{ $basisJahr }}"
                data-aktuelles-jahr="{{ $aktuellesJahr }}">
              <td class="text-center" style="cursor: move;">
                <i class="bi bi-grip-vertical text-muted" data-role="drag-handle" title="Ziehen zum Sortieren"></i>
              </td>
              <td>
                <input type="text" class="form-control" data-field="beschreibung" value="{{ $p->beschreibung }}" {{ $hasId ? '' : 'disabled' }}>
              </td>
              <td>
                <input type="number" class="form-control text-end" data-field="anzahl" step="0.01" min="0" 
                       value="{{ number_format((float)$p->anzahl, 2, '.', '') }}" {{ $hasId ? '' : 'disabled' }}>
              </td>
              <td>
                {{-- ⭐ Tooltip statt sichtbarem Text --}}
                <input type="number" class="form-control text-end" data-field="einzelpreis" step="0.01" min="0" 
                       value="{{ number_format($anzeigePreis, 2, '.', '') }}" 
                       title="{{ $tooltip }}"
                       {{ $hasId ? '' : 'disabled' }}>
              </td>
              <td>
                <input type="text" class="form-control text-end" data-field="gesamtpreis" 
                       value="{{ number_format($rowTotal, 2, ',', '.') }}" disabled>
              </td>
              <td>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" data-field="aktiv" 
                         {{ $p->aktiv ? 'checked' : '' }} {{ $hasId ? '' : 'disabled' }}>
                </div>
              </td>
              <td class="text-end">
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-primary d-none" data-role="btn-save" 
                          title="Speichern" {{ $hasId ? '' : 'disabled' }}>
                    <i class="bi bi-save2"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-danger" data-role="btn-delete" 
                          title="Löschen" {{ $hasId ? '' : 'disabled' }}>
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          @endforeach

        </tbody>
        <tfoot>
          <tr class="table-light">
            <td colspan="4" class="text-end fw-semibold">Summe (aktiv):</td>
            <td>
              <input type="text" class="form-control text-end fw-semibold" id="art-summe-foot"
                     value="{{ number_format($serverSumAktiv, 2, ',', '.') }}" disabled>
            </td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

{{-- Data-Container für JS --}}
<div id="artikel-root"
     data-csrf="{{ csrf_token() }}"
     data-aktuelles-jahr="{{ $aktuellesJahr }}"
     data-route-store="{{ $hasId ? route('gebaeude.artikel.store', $gebaeude->id) : '' }}"
     data-route-update0="{{ route('artikel.gebaeude.update', 0) }}"
     data-route-delete0="{{ route('artikel.gebaeude.destroy', 0) }}"
     data-route-reorder="{{ $hasId ? route('gebaeude.artikel.reorder', $gebaeude->id) : '' }}">
</div>

@verbatim
<script>
(function(){
  var root = document.getElementById('artikel-root');
  var CSRF = (root && root.dataset && root.dataset.csrf) || '';
  var AKTUELLES_JAHR = parseInt((root && root.dataset && root.dataset.aktuellesJahr) || '0');
  var ROUTE_STORE   = (root && root.dataset && root.dataset.routeStore)   || '';
  var ROUTE_UPDATE0 = (root && root.dataset && root.dataset.routeUpdate0) || '';
  var ROUTE_DELETE0 = (root && root.dataset && root.dataset.routeDelete0) || '';
  var ROUTE_REORDER = (root && root.dataset && root.dataset.routeReorder) || '';

  var tbody     = document.getElementById('art-tbody');
  var sumHead   = document.getElementById('art-summe-head');
  var sumFoot   = document.getElementById('art-summe-foot');
  var onlyActive = document.getElementById('art-only-active');

  if (!ROUTE_STORE) return;

  function euro(v){
    var n = Number.isFinite(+v) ? +v : 0;
    return n.toLocaleString('de-DE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function parseNum(v){
    var x = parseFloat(String(v).replace(',', '.'));
    return Number.isFinite(x) ? x : 0;
  }

  function isActive(tr){
    var cb = tr.querySelector('[data-field="aktiv"]');
    return cb ? cb.checked : false;
  }

  function applyActiveFilter(){
    var showOnly = onlyActive && onlyActive.checked;
    (tbody ? tbody.querySelectorAll('tr[data-id]') : []).forEach(function(tr){
      var active = isActive(tr);
      tr.style.display = (showOnly && !active) ? 'none' : '';
    });
  }

  if (onlyActive){
    onlyActive.addEventListener('change', applyActiveFilter);
  }

  function markDirty(tr, dirty){
    var btnSave = tr.querySelector('[data-role="btn-save"]');
    if (!btnSave) return;
    if (dirty) {
      btnSave.classList.remove('d-none');
    } else {
      btnSave.classList.add('d-none');
    }
  }

  function recalcRowTotal(tr){
    var anzahl = parseNum(tr.querySelector('[data-field="anzahl"]').value);
    var preis  = parseNum(tr.querySelector('[data-field="einzelpreis"]').value);
    var total  = anzahl * preis;

    var inp = tr.querySelector('[data-field="gesamtpreis"]');
    if (inp) {
      inp.value = euro(total);
    }
  }

  function recalcSum(){
    var sum = 0;
    (tbody ? tbody.querySelectorAll('tr[data-id]') : []).forEach(function(tr){
      if (!isActive(tr)) return;
      var anzahl = parseNum(tr.querySelector('[data-field="anzahl"]').value);
      var preis  = parseNum(tr.querySelector('[data-field="einzelpreis"]').value);
      sum += (anzahl * preis);
    });

    if (sumHead) sumHead.textContent = euro(sum);
    if (sumFoot) sumFoot.value = euro(sum);
  }

  // ⭐ Neuen Artikel hinzufügen
  var btnAdd = document.getElementById('btn-add-art');
  if (btnAdd){
    btnAdd.addEventListener('click', async function(){
      var beschr = (document.getElementById('new-beschreibung').value || '').trim();
      var anzahl = parseNum(document.getElementById('new-anzahl').value);
      var preis  = parseNum(document.getElementById('new-einzelpreis').value);
      var aktiv  = document.getElementById('new-aktiv').checked;

      if (!beschr) {
        alert('Bitte Beschreibung eingeben!');
        return;
      }

      try {
        var res = await fetch(ROUTE_STORE, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            beschreibung: beschr,
            anzahl: anzahl,
            einzelpreis: preis,
            basis_preis: preis,
            basis_jahr: AKTUELLES_JAHR,  // ⭐ AKTUELLES Jahr!
            aktiv: aktiv
          })
        });

        var isJson = (res.headers.get('content-type') || '').includes('application/json');
        var json = null;

        if (isJson) {
          json = await res.json();
        }

        if (!res.ok || (json && json.ok === false)) {
          throw new Error((json && json.message) || 'Fehler beim Speichern.');
        }

        window.location.reload();
      } catch (err){
        console.error(err);
        alert('Fehler beim Hinzufügen: ' + err.message);
      }
    });
  }

  var newAnzahl = document.getElementById('new-anzahl');
  var newPreis  = document.getElementById('new-einzelpreis');
  var newTotal  = document.getElementById('new-gesamtpreis');

  function updateNewRow(){
    var a = parseNum(newAnzahl.value);
    var p = parseNum(newPreis.value);
    if (newTotal) newTotal.value = euro(a * p);
  }

  if (newAnzahl) newAnzahl.addEventListener('input', updateNewRow);
  if (newPreis)  newPreis.addEventListener('input', updateNewRow);

  // ⭐ Zeile speichern
  async function saveRow(tr){
    var id = tr.getAttribute('data-id');
    if (!id) return;

    var beschr = (tr.querySelector('[data-field="beschreibung"]').value || '').trim();
    var anzahl = parseNum(tr.querySelector('[data-field="anzahl"]').value);
    var preis  = parseNum(tr.querySelector('[data-field="einzelpreis"]').value);
    var cb     = tr.querySelector('[data-field="aktiv"]');
    var aktiv  = cb ? cb.checked : false;

    var url = ROUTE_UPDATE0.replace(/\/0$/, '/' + id);

    try {
      var res = await fetch(url, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          beschreibung: beschr,
          anzahl: anzahl,
          einzelpreis: preis,
          basis_preis: preis,
          basis_jahr: AKTUELLES_JAHR,  // ⭐ AKTUELLES Jahr!
          aktiv: aktiv
        })
      });

      var isJson = (res.headers.get('content-type') || '').includes('application/json');
      if (isJson) {
        var json = await res.json();
        if (!res.ok || json.ok === false) {
          throw new Error(json.message || 'Fehler beim Speichern.');
        }
      } else if (!res.ok) {
        throw new Error('Fehler beim Speichern (HTTP ' + res.status + ').');
      }

      // ⭐ Data-Attribute aktualisieren
      tr.setAttribute('data-basis-preis', preis);
      tr.setAttribute('data-basis-jahr', AKTUELLES_JAHR);

      markDirty(tr, false);
      recalcRowTotal(tr);
      recalcSum();
    } catch (err) {
      console.error(err);
      alert('Fehler beim Speichern: ' + err.message);
      throw err;
    }
  }

  if (tbody){
    tbody.addEventListener('input', function(e){
      var inp = e.target;
      var tr  = inp.closest ? inp.closest('tr[data-id]') : null;
      if (!tr) return;

      if (inp.matches && inp.matches('[data-field="anzahl"], [data-field="einzelpreis"]')) {
        recalcRowTotal(tr);
      }

      markDirty(tr, true);
      recalcSum();
    });

    tbody.addEventListener('change', async function(e){
      var inp = e.target;
      var tr  = inp.closest ? inp.closest('tr[data-id]') : null;
      if (!tr) return;

      if (inp.matches && inp.matches('[data-field="aktiv"]')) {
        var cb = inp;
        var previousChecked = !cb.checked;

        try {
          await saveRow(tr);
          applyActiveFilter();
        } catch (err) {
          cb.checked = previousChecked;
          applyActiveFilter();
          recalcSum();
        }

        return;
      }
    });

    // Drag & Drop
    var dragSrc = null;

    tbody.addEventListener('dragstart', function(e){
      var row = e.target.closest ? e.target.closest('tr[data-id]') : null;
      if (!row) return;
      e.dataTransfer.effectAllowed = 'move';
      dragSrc = row;
      row.classList.add('opacity-50');
    });

    tbody.addEventListener('dragend', function(e){
      if (dragSrc){
        dragSrc.classList.remove('opacity-50');
        dragSrc = null;
      }
    });

    tbody.addEventListener('dragover', function(e){
      if (!dragSrc) return;
      var row = e.target.closest ? e.target.closest('tr[data-id]') : null;
      if (!row || row === dragSrc) return;
      e.preventDefault();
    });

    tbody.addEventListener('drop', async function(e){
      if (!dragSrc) return;
      var target = e.target.closest ? e.target.closest('tr[data-id]') : null;
      if (!target || target === dragSrc) return;
      e.preventDefault();

      var rect = target.getBoundingClientRect();
      var before = (e.clientY - rect.top) < (rect.height / 2);

      if (before) {
        tbody.insertBefore(dragSrc, target);
      } else {
        tbody.insertBefore(dragSrc, target.nextSibling);
      }

      var ids = [];
      (tbody ? tbody.querySelectorAll('tr[data-id]') : []).forEach(function(tr){
        if (tr.style.display !== 'none') {
          ids.push(tr.getAttribute('data-id'));
        }
      });

      try {
        var res = await fetch(ROUTE_REORDER, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
          },
          body: JSON.stringify({ ids: ids })
        });

        var isJson = (res.headers.get('content-type') || '').includes('application/json');
        if (isJson) {
          var json = await res.json();
          if (!res.ok || json.ok === false) {
            throw new Error(json.message || 'Fehler beim Sortieren.');
          }
        } else if (!res.ok) {
          throw new Error('Fehler beim Sortieren (HTTP ' + res.status + ').');
        }
      } catch (err) {
        console.error(err);
        alert('Fehler beim Sortieren: ' + err.message);
      }
    });

    tbody.addEventListener('click', async function(e){
      var btnDel  = e.target.closest ? e.target.closest('[data-role="btn-delete"]') : null;
      var btnSave = e.target.closest ? e.target.closest('[data-role="btn-save"]')   : null;
      var tr      = e.target.closest ? e.target.closest('tr[data-id]')             : null;
      if (!tr) return;
      var id = tr.getAttribute('data-id');

      if (btnDel){
        if (!confirm('Diese Position wirklich löschen?')) return;
        var url = ROUTE_DELETE0.replace(/\/0$/, '/' + id);

        try {
          var res = await fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': CSRF,
              'Accept': 'application/json'
            },
            body: JSON.stringify({ _method: 'DELETE' })
          });

          var isJson = (res.headers.get('content-type') || '').includes('application/json');
          if (isJson){
            var json = await res.json();
            if (!res.ok || json.ok === false){
              throw new Error(json.message || 'Fehler beim Löschen.');
            }
          } else if (!res.ok){
            throw new Error('Fehler beim Löschen (HTTP ' + res.status + ').');
          }

          tr.remove();
          recalcSum();
        } catch (err){
          console.error(err);
          alert('Fehler beim Löschen: ' + err.message);
        }
        return;
      }

      if (btnSave){
        try {
          await saveRow(tr);
        } catch (err) {
          // Fehler schon behandelt
        }
      }
    });
  }

  recalcSum();
})();
</script>
@endverbatim