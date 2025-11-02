{{-- resources/views/gebaeude/partials/_artikel.blade.php --}}
{{-- Editierbare Tabelle für artikel_gebaeude mit Drag&Drop-Sortierung und "Nur aktive anzeigen". --}}

@php
  /** @var \App\Models\Gebaeude $gebaeude */
  $serverSumAktiv = (float) ($gebaeude->artikel_summe_aktiv ?? \App\Models\ArtikelGebaeude::where('gebaeude_id', $gebaeude->id)->where('aktiv', true)->selectRaw('COALESCE(SUM(anzahl * einzelpreis),0) as total')->value('total'));
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
          <input class="form-check-input" type="checkbox" id="art-only-active" checked>
          <label class="form-check-label" for="art-only-active">Nur aktive anzeigen</label>
        </div>
        <div class="text-muted small">
          Summe (aktiv): <span id="art-summe-head">{{ number_format($serverSumAktiv, 2, ',', '.') }}</span> €
        </div>
      </div>
    </div>
    <hr class="mt-2 mb-0">
  </div>

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
              <input type="text" class="form-control" id="new-beschreibung" placeholder="Beschreibung">
            </td>
            <td>
              <input type="number" class="form-control text-end" id="new-anzahl" step="0.01" min="0" value="1">
            </td>
            <td>
              <input type="number" class="form-control text-end" id="new-einzelpreis" step="0.01" min="0" value="0.00">
            </td>
            <td>
              <input type="text" class="form-control text-end" id="new-gesamtpreis" value="0,00" disabled>
            </td>
            <td>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="new-aktiv" checked>
              </div>
            </td>
            <td class="text-end">
              <button type="button" id="btn-add-art" class="btn btn-sm btn-success">
                <i class="bi bi-check2-circle"></i> Hinzufügen
              </button>
            </td>
          </tr>

          {{-- Bestehende Datensätze --}}
          @foreach(($gebaeude->artikel ?? []) as $p)
            @php $rowTotal = (float)$p->anzahl * (float)$p->einzelpreis; @endphp
            <tr data-id="{{ $p->id }}" draggable="true" data-aktiv="{{ $p->aktiv ? '1' : '0' }}">
              <td class="text-center cursor-move" style="cursor: move;">
                <i class="bi bi-grip-vertical text-muted" data-role="drag-handle" title="Ziehen zum Sortieren"></i>
              </td>
              <td>
                <input type="text" class="form-control" data-field="beschreibung" value="{{ $p->beschreibung }}">
              </td>
              <td>
                <input type="number" class="form-control text-end" data-field="anzahl" step="0.01" min="0" value="{{ number_format((float)$p->anzahl, 2, '.', '') }}">
              </td>
              <td>
                <input type="number" class="form-control text-end" data-field="einzelpreis" step="0.01" min="0" value="{{ number_format((float)$p->einzelpreis, 2, '.', '') }}">
              </td>
              <td>
                <input type="text" class="form-control text-end" data-field="gesamtpreis" value="{{ number_format($rowTotal, 2, ',', '.') }}" disabled>
              </td>
              <td>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" data-field="aktiv" {{ $p->aktiv ? 'checked' : '' }}>
                </div>
              </td>
              <td class="text-end">
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-primary d-none" data-role="btn-save" title="Speichern">
                    <i class="bi bi-save2"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-danger" data-role="btn-delete" title="Löschen">
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
            <td><input type="text" class="form-control text-end fw-semibold" id="art-summe-foot" value="{{ number_format($serverSumAktiv, 2, ',', '.') }}" disabled></td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

{{-- Data-Container --}}
<div id="artikel-root"
     data-csrf="{{ csrf_token() }}"
     data-route-store="{{ route('gebaeude.artikel.store', $gebaeude->id) }}"
     data-route-update0="{{ route('artikel.gebaeude.update', 0) }}"
     data-route-delete0="{{ route('artikel.gebaeude.destroy', 0) }}"
     data-route-reorder="{{ route('gebaeude.artikel.reorder', $gebaeude->id) }}">
</div>

@verbatim
<script>
(function(){
  var root=document.getElementById('artikel-root');
  var CSRF=(root&&root.dataset&&root.dataset.csrf)||'';
  var ROUTE_STORE=(root&&root.dataset&&root.dataset.routeStore)||'';
  var ROUTE_UPDATE0=(root&&root.dataset&&root.dataset.routeUpdate0)||'';
  var ROUTE_DELETE0=(root&&root.dataset&&root.dataset.routeDelete0)||'';
  var ROUTE_REORDER=(root&&root.dataset&&root.dataset.routeReorder)||'';

  function euro(v){var n=Number.isFinite(+v)?+v:0;return n.toLocaleString('de-DE',{minimumFractionDigits:2,maximumFractionDigits:2});}
  function parseNum(v){var x=parseFloat(String(v).replace(',','.'));return Number.isFinite(x)?x:0;}

  var tbody=document.getElementById('art-tbody');
  var sumHead=document.getElementById('art-summe-head');
  var sumFoot=document.getElementById('art-summe-foot');
  var onlyActive=document.getElementById('art-only-active');

  function isActive(tr){var cb=tr.querySelector('[data-field="aktiv"]');return cb?cb.checked:false;}

  function applyActiveFilter(){
    var showOnly=onlyActive&&onlyActive.checked;
    (tbody?tbody.querySelectorAll('tr[data-id]'):[]).forEach(function(tr){
      var active=isActive(tr);
      tr.style.display=(showOnly && !active)?'none':'';
    });
  }

  function recalcRowTotal(tr){
    var qty=parseNum(tr.querySelector('[data-field="anzahl"]')&&tr.querySelector('[data-field="anzahl"]').value);
    var price=parseNum(tr.querySelector('[data-field="einzelpreis"]')&&tr.querySelector('[data-field="einzelpreis"]').value);
    var total=qty*price;
    var tot=tr.querySelector('[data-field="gesamtpreis"]');
    if(tot) tot.value=euro(total);
  }

  function recalcSum(){
    var sum=0;
    var showOnly=onlyActive&&onlyActive.checked;
    (tbody?tbody.querySelectorAll('tr[data-id]'):[]).forEach(function(tr){
      if(showOnly && !isActive(tr)) return;
      var qty=parseNum(tr.querySelector('[data-field="anzahl"]')&&tr.querySelector('[data-field="anzahl"]').value);
      var price=parseNum(tr.querySelector('[data-field="einzelpreis"]')&&tr.querySelector('[data-field="einzelpreis"]').value);
      sum+=qty*price;
    });
    var newActive=document.getElementById('new-aktiv');
    var newQty=parseNum(document.getElementById('new-anzahl')&&document.getElementById('new-anzahl').value);
    var newPrice=parseNum(document.getElementById('new-einzelpreis')&&document.getElementById('new-einzelpreis').value);
    var addNew=(newActive&&newActive.checked)?(newQty*newPrice):0;
    if(sumHead) sumHead.textContent=euro(sum+addNew);
    if(sumFoot) sumFoot.value=euro(sum);
  }

  function markDirty(tr,dirty){
    var ind=tr.querySelector('[data-role="dirty-indicator"]');
    var btnSave=tr.querySelector('[data-role="btn-save"]');
    if(dirty){
      if(ind){ind.classList.remove('bi-circle','text-muted');ind.classList.add('bi-record-fill','text-warning');ind.title='Änderungen nicht gespeichert';}
      if(btnSave) btnSave.classList.remove('d-none');
      tr.classList.add('table-warning');
    } else {
      if(ind){ind.classList.remove('bi-record-fill','text-warning');ind.classList.add('bi-circle','text-muted');ind.title='Keine Änderungen';}
      if(btnSave) btnSave.classList.add('d-none');
      tr.classList.remove('table-warning');
    }
  }

  var newQtyEl=document.getElementById('new-anzahl');
  var newPriceEl=document.getElementById('new-einzelpreis');
  var newTotEl=document.getElementById('new-gesamtpreis');
  var newAktivEl=document.getElementById('new-aktiv');

  function recalcNewTotal(){
    var tot=parseNum(newQtyEl&&newQtyEl.value)*parseNum(newPriceEl&&newPriceEl.value);
    if(newTotEl) newTotEl.value=euro(tot);
    recalcSum();
  }
  [newQtyEl,newPriceEl,newAktivEl,onlyActive].forEach(function(el){if(el)el.addEventListener('input',function(){applyActiveFilter();recalcNewTotal();});});
  applyActiveFilter();recalcNewTotal();

  ['new-beschreibung','new-anzahl','new-einzelpreis'].forEach(function(id){
    var el=document.getElementById(id);
    if(!el) return;
    el.addEventListener('keydown',function(e){
      if(e.key==='Enter'){e.preventDefault();var b=document.getElementById('btn-add-art');if(b)b.click();}
    });
  });

  var btnAdd=document.getElementById('btn-add-art');
  if(btnAdd){
    btnAdd.addEventListener('click',async function(){
      var beschr=(document.getElementById('new-beschreibung')&&document.getElementById('new-beschreibung').value||'').trim();
      var anzahl=parseNum(newQtyEl&&newQtyEl.value);
      var preis=parseNum(newPriceEl&&newPriceEl.value);
      var aktiv=(newAktivEl&&newAktivEl.checked)?1:0;
      if(!beschr){alert('Bitte eine Beschreibung eingeben.');return;}
      try{
        var res=await fetch(ROUTE_STORE,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({beschreibung:beschr,anzahl:anzahl,einzelpreis:preis,aktiv:aktiv})});
        var isJson=(res.headers.get('content-type')||'').includes('application/json');
        if(isJson){
          var json=await res.json();
          if(!res.ok||json.ok===false){throw new Error(json.message||'Fehler beim Hinzufügen.');}
        }else if(!res.ok){throw new Error('Fehler beim Hinzufügen (HTTP '+res.status+').');}
        window.location.reload();
      }catch(err){console.error(err);alert('Fehler beim Hinzufügen: '+err.message);}
    });
  }

  if(tbody){
    tbody.addEventListener('input',function(e){
      var inp=e.target;
      var tr=inp.closest?inp.closest('tr[data-id]'):null;
      if(!tr) return;
      if(inp.matches&&inp.matches('[data-field="anzahl"], [data-field="einzelpreis"]')){recalcRowTotal(tr);}
      markDirty(tr,true);
      recalcSum();
    });

    tbody.addEventListener('change',function(e){
      var inp=e.target;
      var tr=inp.closest?inp.closest('tr[data-id]'):null;
      if(!tr) return;
      if(inp.matches&&inp.matches('[data-field="aktiv"]')){markDirty(tr,true);applyActiveFilter();recalcSum();}
    });

    var dragSrc=null;
    tbody.addEventListener('dragstart',function(e){
      var row=e.target.closest?e.target.closest('tr[data-id]'):null;
      if(!row) return;e.dataTransfer.effectAllowed='move';dragSrc=row;row.classList.add('opacity-50');
    });
    tbody.addEventListener('dragend',function(e){
      if(dragSrc){dragSrc.classList.remove('opacity-50');dragSrc=null;}
    });
    tbody.addEventListener('dragover',function(e){
      if(!dragSrc) return;var row=e.target.closest?e.target.closest('tr[data-id]'):null;if(!row||row===dragSrc) return;
      e.preventDefault();
    });
    tbody.addEventListener('drop',async function(e){
      if(!dragSrc) return;
      var target=e.target.closest?e.target.closest('tr[data-id]'):null;
      if(!target||target===dragSrc) return;
      e.preventDefault();
      var rect=target.getBoundingClientRect();
      var before=(e.clientY-rect.top)<(rect.height/2);
      if(before) tbody.insertBefore(dragSrc,target); else tbody.insertBefore(dragSrc,target.nextSibling);

      var ids=[]; (tbody?tbody.querySelectorAll('tr[data-id]'):[]).forEach(function(tr){ if(tr.style.display!=='none') ids.push(tr.getAttribute('data-id')); });

      try{
        var res=await fetch(ROUTE_REORDER,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({ids:ids})});
        var isJson=(res.headers.get('content-type')||'').includes('application/json');
        if(isJson){var json=await res.json(); if(!res.ok||json.ok===false){throw new Error(json.message||'Fehler beim Sortieren.');}}
        else if(!res.ok){throw new Error('Fehler beim Sortieren (HTTP '+res.status+').');}
      }catch(err){console.error(err);alert('Fehler beim Sortieren: '+err.message);}
    });

    tbody.addEventListener('click',async function(e){
      var btnDel=e.target.closest?e.target.closest('[data-role="btn-delete"]'):null;
      var btnSave=e.target.closest?e.target.closest('[data-role="btn-save"]'):null;
      var tr=e.target.closest?e.target.closest('tr[data-id]'):null;
      if(!tr) return;
      var id=tr.getAttribute('data-id');

      if(btnDel){
        if(!confirm('Diese Position wirklich löschen?')) return;
        var url=ROUTE_DELETE0.replace(/\/0$/, '/'+id);
        try{
          var res=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({_method:'DELETE'})});
          var isJson=(res.headers.get('content-type')||'').includes('application/json');
          if(isJson){var json=await res.json(); if(!res.ok||json.ok===false){throw new Error(json.message||'Fehler beim Löschen.');}}
          else if(!res.ok){throw new Error('Fehler beim Löschen (HTTP '+res.status+').');}
          tr.remove();recalcSum();
        }catch(err){console.error(err);alert('Fehler beim Löschen: '+err.message);}
        return;
      }

      if(btnSave){
        var beschr=(tr.querySelector('[data-field="beschreibung"]')&&tr.querySelector('[data-field="beschreibung"]').value||'').trim();
        var anzahl=parseNum(tr.querySelector('[data-field="anzahl"]')&&tr.querySelector('[data-field="anzahl"]').value);
        var preis=parseNum(tr.querySelector('[data-field="einzelpreis"]')&&tr.querySelector('[data-field="einzelpreis"]').value);
        var aktivCb=tr.querySelector('[data-field="aktiv"]');
        var aktiv=aktivCb&&aktivCb.checked?1:0;
        if(!beschr){alert('Beschreibung darf nicht leer sein.');return;}
        var url2=ROUTE_UPDATE0.replace(/\/0$/, '/'+id);
        try{
          var res2=await fetch(url2,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({_method:'PUT',beschreibung:beschr,anzahl:anzahl,einzelpreis:preis,aktiv:aktiv})});
          var isJson2=(res2.headers.get('content-type')||'').includes('application/json');
          if(isJson2){var json2=await res2.json(); if(!res2.ok||json2.ok===false){throw new Error(json2.message||'Fehler beim Speichern.');}}
          else if(!res2.ok){throw new Error('Fehler beim Speichern (HTTP '+res2.status+').');}
          markDirty(tr,false);recalcRowTotal(tr);recalcSum();
        }catch(err){console.error(err);alert('Fehler beim Speichern: '+err.message);}
      }
    });
  }

  recalcSum();
})();
</script>
@endverbatim
