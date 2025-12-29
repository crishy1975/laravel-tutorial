{{-- resources/views/gebaeude/partials/_artikel.blade.php --}}
{{-- MOBIL-OPTIMIERT: Card-Layout auf Smartphones, Tabelle auf Desktop --}}

@php
  /** @var \App\Models\Gebaeude $gebaeude */
  $hasId = isset($gebaeude) && $gebaeude?->exists;
  $aktuellesJahr = now()->year;
  $naechstesJahr = $aktuellesJahr + 1;

  if ($hasId) {
      $aufschlagNaechstesJahr = $gebaeude->getAufschlagProzent($naechstesJahr);
      $hatIndividuell = $gebaeude->hatIndividuellenAufschlag();
      
      // ⭐ FIX: Nur aktive Artikel für Summe verwenden
      $serverSumAktiv = 0.0;
      foreach (($gebaeude->aktiveArtikel ?? []) as $artikel) {
          $basisJahr = $artikel->basis_jahr ?? $aktuellesJahr;
          $basisPreis = $artikel->basis_preis ?? $artikel->einzelpreis;
          
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

  // Alle Artikel für die Anzeige (aktiv + inaktiv)
  $artikelListe = $hasId ? ($gebaeude->artikel ?? []) : [];
@endphp

<div class="row g-3">

  {{-- Header - kompakter auf Mobile --}}
  <div class="col-12">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-receipt text-muted"></i>
        <span class="fw-semibold">Artikel / Positionen</span>
      </div>
      <div class="d-flex flex-wrap align-items-center gap-3">
        <div class="form-check form-switch m-0">
          <input class="form-check-input" type="checkbox" id="art-only-active" checked {{ $hasId ? '' : 'disabled' }}>
          <label class="form-check-label small" for="art-only-active">Nur aktive</label>
        </div>
        <div class="text-muted small fw-semibold">
          Summe: <span id="art-summe-head" class="text-primary">{{ number_format($serverSumAktiv, 2, ',', '.') }}</span> EUR
        </div>
      </div>
    </div>
    
    @if($hasId && $aufschlagNaechstesJahr != 0)
    <div class="alert alert-info py-2 px-3 mt-2 mb-0 small">
      <i class="bi bi-info-circle"></i>
      <strong>Aufschlag {{ $naechstesJahr }}:</strong> 
      @if($aufschlagNaechstesJahr > 0)
        <span class="text-success">+{{ number_format($aufschlagNaechstesJahr, 2, ',', '.') }}%</span>
      @else
        <span class="text-danger">{{ number_format($aufschlagNaechstesJahr, 2, ',', '.') }}%</span>
      @endif
      @if($hatIndividuell)
        <span class="badge bg-warning text-dark">Indiv.</span>
      @endif
    </div>
    @endif
    
    <hr class="mt-2 mb-0">
  </div>

  @unless($hasId)
    <div class="col-12">
      <div class="alert alert-info py-2 mb-0 small">
        <i class="bi bi-info-circle"></i>
        Bitte Gebaeude zuerst speichern - danach koennen Artikel erfasst werden.
      </div>
    </div>
  @endunless

  {{-- MOBILE: Neu-Eingabe als Card --}}
  <div class="col-12 d-md-none">
    <div class="card bg-light border-success" id="art-new-card">
      <div class="card-body p-2">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="bi bi-plus-circle text-success"></i>
          <strong class="small">Neuer Artikel</strong>
        </div>
        <div class="row g-2">
          <div class="col-12">
            <input type="text" class="form-control form-control-sm" id="new-beschreibung-mobile" 
                   placeholder="Beschreibung" {{ $hasId ? '' : 'disabled' }}>
          </div>
          <div class="col-6">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Anz</span>
              <input type="number" class="form-control text-end" id="new-anzahl-mobile" 
                     step="0.01" min="0" value="1" {{ $hasId ? '' : 'disabled' }}>
            </div>
          </div>
          <div class="col-6">
            <div class="input-group input-group-sm">
              <span class="input-group-text">EUR</span>
              <input type="number" class="form-control text-end" id="new-einzelpreis-mobile" 
                     step="0.01" min="0" value="0.00" {{ $hasId ? '' : 'disabled' }}>
            </div>
          </div>
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="new-aktiv-mobile" {{ $hasId ? 'checked' : 'disabled' }}>
                <label class="form-check-label small" for="new-aktiv-mobile">Aktiv</label>
              </div>
              <button type="button" id="btn-add-art-mobile" class="btn btn-sm btn-success" {{ $hasId ? '' : 'disabled' }}>
                <i class="bi bi-check2-circle"></i> Hinzufuegen
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- MOBILE: Artikel als Cards --}}
  <div class="col-12 d-md-none" id="art-cards-mobile">
    @foreach($artikelListe as $p)
      @php 
        $basisJahr = $p->basis_jahr ?? $aktuellesJahr;
        $basisPreis = $p->basis_preis ?? $p->einzelpreis;
        $anzeigePreis = $gebaeude->berechnePreisMitKumulativerErhoehung($basisPreis, $basisJahr, $aktuellesJahr);
        $rowTotal = (float)$p->anzahl * $anzeigePreis;
      @endphp
      <div class="card mb-2 art-card-mobile {{ $p->aktiv ? '' : 'opacity-50' }}" 
           data-id="{{ $p->id }}" 
           data-aktiv="{{ $p->aktiv ? '1' : '0' }}"
           data-basis-preis="{{ $basisPreis }}"
           data-basis-jahr="{{ $basisJahr }}">
        <div class="card-body p-2">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="flex-grow-1 me-2">
              <input type="text" class="form-control form-control-sm fw-semibold" 
                     data-field="beschreibung" value="{{ $p->beschreibung }}">
            </div>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-primary d-none" data-role="btn-save">
                <i class="bi bi-save2"></i>
              </button>
              <button type="button" class="btn btn-outline-danger" data-role="btn-delete">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
          <div class="row g-2">
            <div class="col-4">
              <label class="form-label small text-muted mb-0">Anzahl</label>
              <input type="number" class="form-control form-control-sm text-end" 
                     data-field="anzahl" step="0.01" value="{{ number_format((float)$p->anzahl, 2, '.', '') }}">
            </div>
            <div class="col-4">
              <label class="form-label small text-muted mb-0">Preis</label>
              <input type="number" class="form-control form-control-sm text-end" 
                     data-field="einzelpreis" step="0.01" value="{{ number_format($anzeigePreis, 2, '.', '') }}">
            </div>
            <div class="col-4">
              <label class="form-label small text-muted mb-0">Gesamt</label>
              <input type="text" class="form-control form-control-sm text-end bg-light" 
                     data-field="gesamtpreis" value="{{ number_format($rowTotal, 2, ',', '.') }}" disabled>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
            <div class="form-check form-switch m-0">
              <input class="form-check-input" type="checkbox" data-field="aktiv" {{ $p->aktiv ? 'checked' : '' }}>
              <label class="form-check-label small">Aktiv</label>
            </div>
            <small class="text-muted">Basis: {{ $basisJahr }}</small>
          </div>
        </div>
      </div>
    @endforeach
    
    @if(count($artikelListe) == 0 && $hasId)
      <div class="text-center text-muted py-4">
        <i class="bi bi-inbox fs-1"></i>
        <p class="mb-0 mt-2">Keine Artikel vorhanden</p>
      </div>
    @endif
  </div>

  {{-- DESKTOP: Klassische Tabelle --}}
  <div class="col-12 d-none d-md-block">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="art-table">
        <thead class="table-light">
          <tr>
            <th style="width: 40px;"><i class="bi bi-grip-vertical"></i></th>
            <th>Beschreibung</th>
            <th style="width: 100px;">Anzahl</th>
            <th style="width: 120px;">Einzelpreis</th>
            <th style="width: 120px;">Gesamt</th>
            <th style="width: 80px;">Aktiv</th>
            <th class="text-end" style="width: 100px;">Aktionen</th>
          </tr>
        </thead>
        <tbody id="art-tbody">
          {{-- Eingabezeile: Neu --}}
          <tr id="art-new-row" class="table-secondary">
            <td class="text-center"><i class="bi bi-plus-circle text-success"></i></td>
            <td>
              <input type="text" class="form-control form-control-sm" id="new-beschreibung" 
                     placeholder="Beschreibung" {{ $hasId ? '' : 'disabled' }}>
            </td>
            <td>
              <input type="number" class="form-control form-control-sm text-end" id="new-anzahl" 
                     step="0.01" min="0" value="1" {{ $hasId ? '' : 'disabled' }}>
            </td>
            <td>
              <input type="number" class="form-control form-control-sm text-end" id="new-einzelpreis" 
                     step="0.01" min="0" value="0.00" {{ $hasId ? '' : 'disabled' }}>
            </td>
            <td>
              <input type="text" class="form-control form-control-sm text-end bg-light" id="new-gesamtpreis" 
                     value="0,00" disabled>
            </td>
            <td class="text-center">
              <input class="form-check-input" type="checkbox" id="new-aktiv" {{ $hasId ? 'checked' : 'disabled' }}>
            </td>
            <td class="text-end">
              <button type="button" id="btn-add-art" class="btn btn-sm btn-success" {{ $hasId ? '' : 'disabled' }}>
                <i class="bi bi-plus-lg"></i>
              </button>
            </td>
          </tr>

          {{-- Bestehende Artikel --}}
          @foreach($artikelListe as $p)
            @php 
              $basisJahr = $p->basis_jahr ?? $aktuellesJahr;
              $basisPreis = $p->basis_preis ?? $p->einzelpreis;
              $anzeigePreis = $gebaeude->berechnePreisMitKumulativerErhoehung($basisPreis, $basisJahr, $aktuellesJahr);
              $rowTotal = (float)$p->anzahl * $anzeigePreis;
            @endphp
            <tr data-id="{{ $p->id }}" 
                data-aktiv="{{ $p->aktiv ? '1' : '0' }}"
                data-basis-preis="{{ $basisPreis }}"
                data-basis-jahr="{{ $basisJahr }}"
                draggable="true"
                class="{{ $p->aktiv ? '' : 'opacity-50' }}">
              <td class="text-center text-muted" style="cursor: grab;"><i class="bi bi-grip-vertical"></i></td>
              <td>
                <input type="text" class="form-control form-control-sm" data-field="beschreibung" 
                       value="{{ $p->beschreibung }}">
              </td>
              <td>
                <input type="number" class="form-control form-control-sm text-end" data-field="anzahl" 
                       step="0.01" value="{{ number_format((float)$p->anzahl, 2, '.', '') }}">
              </td>
              <td>
                <input type="number" class="form-control form-control-sm text-end" data-field="einzelpreis" 
                       step="0.01" value="{{ number_format($anzeigePreis, 2, '.', '') }}">
              </td>
              <td>
                <input type="text" class="form-control form-control-sm text-end bg-light" data-field="gesamtpreis" 
                       value="{{ number_format($rowTotal, 2, ',', '.') }}" disabled>
              </td>
              <td class="text-center">
                <input class="form-check-input" type="checkbox" data-field="aktiv" {{ $p->aktiv ? 'checked' : '' }}>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-primary d-none" data-role="btn-save">
                    <i class="bi bi-save2"></i>
                  </button>
                  <button type="button" class="btn btn-outline-danger" data-role="btn-delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
        <tfoot class="table-light">
          <tr class="fw-bold">
            <td colspan="4" class="text-end">Summe (aktiv):</td>
            <td class="text-end" id="art-summe-foot">{{ number_format($serverSumAktiv, 2, ',', '.') }} EUR</td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

</div>

{{-- ═══════════════════════════════════════════════════════════════════════════════ --}}
{{-- JAVASCRIPT --}}
{{-- ═══════════════════════════════════════════════════════════════════════════════ --}}

@if($hasId)
<div id="art-root"
     data-csrf="{{ csrf_token() }}"
     data-route-store="{{ route('gebaeude.artikel.store', $gebaeude->id) }}"
     data-route-update0="{{ route('artikel.gebaeude.update', 0) }}"
     data-route-delete0="{{ route('artikel.gebaeude.destroy', 0) }}"
     data-route-reorder="{{ route('gebaeude.artikel.reorder', $gebaeude->id) }}"
     data-aktuelles-jahr="{{ $aktuellesJahr }}">
</div>

<script>
(function() {
  var root = document.getElementById('art-root');
  if (!root) return;

  var CSRF = root.dataset.csrf;
  var ROUTE_STORE = root.dataset.routeStore;
  var ROUTE_UPDATE0 = root.dataset.routeUpdate0;
  var ROUTE_DELETE0 = root.dataset.routeDelete0;
  var ROUTE_REORDER = root.dataset.routeReorder;
  var AKTUELLES_JAHR = parseInt(root.dataset.aktuellesJahr, 10);

  // Desktop Elements
  var tbody = document.getElementById('art-tbody');
  var sumHead = document.getElementById('art-summe-head');
  var sumFoot = document.getElementById('art-summe-foot');
  var filterSwitch = document.getElementById('art-only-active');

  var newBeschreibung = document.getElementById('new-beschreibung');
  var newAnzahl = document.getElementById('new-anzahl');
  var newEinzelpreis = document.getElementById('new-einzelpreis');
  var newGesamtpreis = document.getElementById('new-gesamtpreis');
  var newAktiv = document.getElementById('new-aktiv');
  var btnAdd = document.getElementById('btn-add-art');

  // Mobile Elements
  var cardsContainer = document.getElementById('art-cards-mobile');
  var newBeschreibungM = document.getElementById('new-beschreibung-mobile');
  var newAnzahlM = document.getElementById('new-anzahl-mobile');
  var newEinzelpreisM = document.getElementById('new-einzelpreis-mobile');
  var newAktivM = document.getElementById('new-aktiv-mobile');
  var btnAddM = document.getElementById('btn-add-art-mobile');

  function parseNum(v) {
    return parseFloat(String(v).replace(',', '.')) || 0;
  }

  function formatDE(n) {
    return n.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function recalcSum() {
    var sum = 0;
    
    // ⭐ FIX: Nur den SICHTBAREN Container verwenden (nicht beide!)
    var isMobile = window.innerWidth < 768;
    
    if (!isMobile && tbody) {
      // Desktop
      tbody.querySelectorAll('tr[data-id]').forEach(function(tr) {
        var cb = tr.querySelector('[data-field="aktiv"]');
        if (!cb || !cb.checked) return;
        var anz = parseNum(tr.querySelector('[data-field="anzahl"]').value);
        var ep = parseNum(tr.querySelector('[data-field="einzelpreis"]').value);
        sum += anz * ep;
      });
    } else if (isMobile && cardsContainer) {
      // Mobile
      cardsContainer.querySelectorAll('.art-card-mobile[data-id]').forEach(function(card) {
        var cb = card.querySelector('[data-field="aktiv"]');
        if (!cb || !cb.checked) return;
        var anz = parseNum(card.querySelector('[data-field="anzahl"]').value);
        var ep = parseNum(card.querySelector('[data-field="einzelpreis"]').value);
        sum += anz * ep;
      });
    }
    
    var formatted = formatDE(sum);
    if (sumHead) sumHead.textContent = formatted;
    if (sumFoot) sumFoot.textContent = formatted + ' EUR';
  }

  function recalcRowTotal(container) {
    var anz = parseNum(container.querySelector('[data-field="anzahl"]').value);
    var ep = parseNum(container.querySelector('[data-field="einzelpreis"]').value);
    var total = anz * ep;
    var field = container.querySelector('[data-field="gesamtpreis"]');
    if (field) field.value = formatDE(total);
  }

  function applyActiveFilter() {
    var onlyActive = filterSwitch && filterSwitch.checked;
    // Desktop
    if (tbody) {
      tbody.querySelectorAll('tr[data-id]').forEach(function(tr) {
        var cb = tr.querySelector('[data-field="aktiv"]');
        var isActive = cb && cb.checked;
        tr.style.display = (onlyActive && !isActive) ? 'none' : '';
      });
    }
    // Mobile
    if (cardsContainer) {
      cardsContainer.querySelectorAll('.art-card-mobile[data-id]').forEach(function(card) {
        var cb = card.querySelector('[data-field="aktiv"]');
        var isActive = cb && cb.checked;
        card.style.display = (onlyActive && !isActive) ? 'none' : '';
        card.classList.toggle('opacity-50', !isActive);
      });
    }
    recalcSum();
  }

  if (filterSwitch) {
    filterSwitch.addEventListener('change', applyActiveFilter);
  }

  function markDirty(container, dirty) {
    var btn = container.querySelector('[data-role="btn-save"]');
    if (btn) btn.classList.toggle('d-none', !dirty);
  }

  // Desktop: Gesamtpreis bei Eingabe neu berechnen
  if (newAnzahl && newEinzelpreis && newGesamtpreis) {
    [newAnzahl, newEinzelpreis].forEach(function(el) {
      el.addEventListener('input', function() {
        var total = parseNum(newAnzahl.value) * parseNum(newEinzelpreis.value);
        newGesamtpreis.value = formatDE(total);
      });
    });
  }

  // Mobile: Gesamtpreis bei Eingabe neu berechnen
  if (newAnzahlM && newEinzelpreisM) {
    [newAnzahlM, newEinzelpreisM].forEach(function(el) {
      el.addEventListener('input', function() {
        // Mobile hat kein Gesamtpreis-Feld in der Eingabe
      });
    });
  }

  async function addArtikel(beschreibung, anzahl, einzelpreis, aktiv) {
    if (!beschreibung) {
      alert('Bitte Beschreibung eingeben.');
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
          beschreibung: beschreibung,
          anzahl: anzahl,
          einzelpreis: einzelpreis,
          basis_preis: einzelpreis,
          basis_jahr: AKTUELLES_JAHR,
          aktiv: aktiv
        })
      });

      var isJson = (res.headers.get('content-type') || '').includes('application/json');
      if (isJson) {
        var json = await res.json();
        if (!res.ok || json.ok === false) {
          throw new Error(json.message || 'Fehler');
        }
      }

      window.location.reload();
    } catch (err) {
      alert('Fehler: ' + err.message);
    }
  }

  // Desktop hinzufuegen
  if (btnAdd) {
    btnAdd.addEventListener('click', function() {
      addArtikel(
        newBeschreibung.value.trim(),
        parseNum(newAnzahl.value),
        parseNum(newEinzelpreis.value),
        newAktiv.checked
      );
    });
  }

  // Mobile hinzufuegen
  if (btnAddM) {
    btnAddM.addEventListener('click', function() {
      addArtikel(
        newBeschreibungM.value.trim(),
        parseNum(newAnzahlM.value),
        parseNum(newEinzelpreisM.value),
        newAktivM.checked
      );
    });
  }

  async function saveRow(container) {
    var id = container.getAttribute('data-id');
    if (!id) return;

    var beschr = container.querySelector('[data-field="beschreibung"]').value.trim();
    var anzahl = parseNum(container.querySelector('[data-field="anzahl"]').value);
    var preis = parseNum(container.querySelector('[data-field="einzelpreis"]').value);
    var cb = container.querySelector('[data-field="aktiv"]');
    var aktiv = cb ? cb.checked : false;

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
          basis_jahr: AKTUELLES_JAHR,
          aktiv: aktiv
        })
      });

      var isJson = (res.headers.get('content-type') || '').includes('application/json');
      if (isJson) {
        var json = await res.json();
        if (!res.ok || json.ok === false) {
          throw new Error(json.message || 'Fehler');
        }
      }

      container.setAttribute('data-basis-preis', preis);
      container.setAttribute('data-basis-jahr', AKTUELLES_JAHR);
      markDirty(container, false);
      recalcRowTotal(container);
      recalcSum();
    } catch (err) {
      alert('Fehler: ' + err.message);
      throw err;
    }
  }

  async function deleteRow(container) {
    var id = container.getAttribute('data-id');
    if (!id) return;
    if (!confirm('Position wirklich loeschen?')) return;

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
      if (isJson) {
        var json = await res.json();
        if (!res.ok || json.ok === false) {
          throw new Error(json.message || 'Fehler');
        }
      }

      container.remove();
      recalcSum();
    } catch (err) {
      alert('Fehler: ' + err.message);
    }
  }

  // Event-Handler fuer Desktop-Tabelle
  if (tbody) {
    tbody.addEventListener('input', function(e) {
      var tr = e.target.closest('tr[data-id]');
      if (!tr) return;
      if (e.target.matches('[data-field="anzahl"], [data-field="einzelpreis"]')) {
        recalcRowTotal(tr);
      }
      markDirty(tr, true);
      recalcSum();
    });

    tbody.addEventListener('change', async function(e) {
      var tr = e.target.closest('tr[data-id]');
      if (!tr) return;
      if (e.target.matches('[data-field="aktiv"]')) {
        // ⭐ Summe SOFORT aktualisieren
        recalcSum();
        try {
          await saveRow(tr);
          applyActiveFilter();
        } catch (err) {
          e.target.checked = !e.target.checked;
          recalcSum();  // Bei Fehler zurücksetzen
          applyActiveFilter();
        }
      }
    });

    tbody.addEventListener('click', async function(e) {
      var tr = e.target.closest('tr[data-id]');
      if (!tr) return;

      if (e.target.closest('[data-role="btn-delete"]')) {
        await deleteRow(tr);
      }
      if (e.target.closest('[data-role="btn-save"]')) {
        await saveRow(tr);
      }
    });

    // Drag & Drop fuer Desktop
    var dragSrc = null;
    tbody.addEventListener('dragstart', function(e) {
      var row = e.target.closest('tr[data-id]');
      if (!row) return;
      dragSrc = row;
      row.classList.add('opacity-50');
    });
    tbody.addEventListener('dragend', function() {
      if (dragSrc) dragSrc.classList.remove('opacity-50');
      dragSrc = null;
    });
    tbody.addEventListener('dragover', function(e) {
      if (!dragSrc) return;
      var row = e.target.closest('tr[data-id]');
      if (!row || row === dragSrc) return;
      e.preventDefault();
    });
    tbody.addEventListener('drop', async function(e) {
      if (!dragSrc) return;
      var target = e.target.closest('tr[data-id]');
      if (!target || target === dragSrc) return;
      e.preventDefault();

      var rect = target.getBoundingClientRect();
      var before = (e.clientY - rect.top) < (rect.height / 2);
      tbody.insertBefore(dragSrc, before ? target : target.nextSibling);

      var ids = [];
      tbody.querySelectorAll('tr[data-id]').forEach(function(tr) {
        ids.push(tr.getAttribute('data-id'));
      });

      await fetch(ROUTE_REORDER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ ids: ids })
      });
    });
  }

  // Event-Handler fuer Mobile-Cards
  if (cardsContainer) {
    cardsContainer.addEventListener('input', function(e) {
      var card = e.target.closest('.art-card-mobile[data-id]');
      if (!card) return;
      if (e.target.matches('[data-field="anzahl"], [data-field="einzelpreis"]')) {
        recalcRowTotal(card);
      }
      markDirty(card, true);
      recalcSum();
    });

    cardsContainer.addEventListener('change', async function(e) {
      var card = e.target.closest('.art-card-mobile[data-id]');
      if (!card) return;
      if (e.target.matches('[data-field="aktiv"]')) {
        // ⭐ Summe SOFORT aktualisieren
        recalcSum();
        try {
          await saveRow(card);
          applyActiveFilter();
        } catch (err) {
          e.target.checked = !e.target.checked;
          recalcSum();  // Bei Fehler zurücksetzen
          applyActiveFilter();
        }
      }
    });

    cardsContainer.addEventListener('click', async function(e) {
      var card = e.target.closest('.art-card-mobile[data-id]');
      if (!card) return;

      if (e.target.closest('[data-role="btn-delete"]')) {
        await deleteRow(card);
      }
      if (e.target.closest('[data-role="btn-save"]')) {
        await saveRow(card);
      }
    });
  }

  applyActiveFilter();
  recalcSum();
})();
</script>
@endif
