{{-- resources/views/gebaeude/partials/_touren.blade.php --}}
{{-- MOBIL-OPTIMIERT: Vertikal gestapelt auf Smartphones --}}

@php
$hasId = isset($gebaeude) && $gebaeude?->exists;
$zugeordnet = $hasId ? $gebaeude->touren : collect();
$zugeordnetIds = $zugeordnet->pluck('id')->toArray();
$verfuegbar = ($tourenAlle ?? collect())->reject(fn($t) => in_array($t->id, $zugeordnetIds));
@endphp

<div class="row g-3">
  
  {{-- Header --}}
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-map text-primary"></i>
        <span class="fw-semibold">Touren-Zuordnung</span>
      </div>
      @if($hasId)
      <small class="text-muted d-none d-md-inline">
        <i class="bi bi-info-circle"></i> Drag & Drop oder Buttons verwenden
      </small>
      @endif
    </div>
    <hr class="mt-2 mb-0">
  </div>

  @unless($hasId)
  <div class="col-12">
    <div class="alert alert-info mb-0 py-2 small">
      <i class="bi bi-info-circle"></i> Touren erst nach Speichern zuordnen.
    </div>
  </div>
  @else

  {{-- MOBILE: Kompaktes Layout --}}
  <div class="col-12 d-md-none">
    {{-- Zugeordnete Touren --}}
    <div class="card border-primary mb-3">
      <div class="card-header bg-primary text-white py-2">
        <div class="d-flex justify-content-between align-items-center">
          <span><i class="bi bi-building"></i> Zugeordnet</span>
          <span class="badge bg-white text-primary" id="zugeordnet-count-mobile">{{ $zugeordnet->count() }}</span>
        </div>
      </div>
      <div class="card-body p-2" id="zugeordnete-touren-mobile">
        @forelse($zugeordnet as $tour)
        <div class="tour-item-mobile d-flex align-items-center justify-content-between py-2 border-bottom" data-tour-id="{{ $tour->id }}">
          <div class="d-flex align-items-center gap-2 flex-grow-1">
            <span class="badge bg-primary tour-position">{{ $loop->iteration }}</span>
            <div>
              <div class="fw-semibold small">{{ $tour->name }}</div>
              @if($tour->beschreibung)
              <div class="text-muted small">{{ Str::limit($tour->beschreibung, 30) }}</div>
              @endif
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger remove-tour-mobile ms-2" data-tour-id="{{ $tour->id }}" data-tour-name="{{ $tour->name }}" data-tour-beschreibung="{{ Str::limit($tour->beschreibung ?? '', 30) }}">
            <i class="bi bi-x-lg"></i>
          </button>
          <input type="hidden" name="tour_ids[]" value="{{ $tour->id }}">
        </div>
        @empty
        <div class="text-center text-muted py-3 small empty-hint">
          <i class="bi bi-inbox"></i> Keine Touren zugeordnet
        </div>
        @endforelse
      </div>
    </div>

    {{-- Verfuegbare Touren --}}
    <div class="card">
      <div class="card-header bg-light py-2">
        <div class="d-flex justify-content-between align-items-center">
          <span><i class="bi bi-inbox"></i> Verfuegbar</span>
          <span class="badge bg-secondary" id="verfuegbar-count-mobile">{{ $verfuegbar->count() }}</span>
        </div>
      </div>
      <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;" id="verfuegbare-touren-mobile">
        @forelse($verfuegbar as $tour)
        <div class="tour-item-mobile d-flex align-items-center justify-content-between py-2 border-bottom" data-tour-id="{{ $tour->id }}">
          <div class="flex-grow-1">
            <div class="fw-semibold small">{{ $tour->name }}</div>
            @if($tour->beschreibung)
            <div class="text-muted small">{{ Str::limit($tour->beschreibung, 30) }}</div>
            @endif
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary add-tour-mobile ms-2" data-tour-id="{{ $tour->id }}" data-tour-name="{{ $tour->name }}" data-tour-beschreibung="{{ Str::limit($tour->beschreibung ?? '', 30) }}">
            <i class="bi bi-plus-lg"></i>
          </button>
        </div>
        @empty
        <div class="text-center text-muted py-3 small empty-hint">
          <i class="bi bi-check-circle"></i> Alle Touren zugeordnet
        </div>
        @endforelse
      </div>
    </div>
  </div>

  {{-- DESKTOP: Two-Panel Layout --}}
  <div class="col-12 d-none d-md-block">
    <div class="row g-3">
      {{-- LEFT: Verfuegbare Touren --}}
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-light">
            <h6 class="mb-0">
              <i class="bi bi-inbox"></i> Verfuegbar
              <span class="badge bg-secondary ms-2" id="verfuegbar-count">{{ $verfuegbar->count() }}</span>
            </h6>
          </div>
          <div class="card-body p-2" style="max-height: 400px; overflow-y: auto;">
            <div id="verfuegbare-touren" class="tour-list">
              @forelse($verfuegbar as $tour)
              <div class="tour-card" data-tour-id="{{ $tour->id }}" draggable="true">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-grip-vertical text-muted"></i>
                  <div class="flex-grow-1">
                    <div class="fw-semibold small">{{ $tour->name }}</div>
                    @if($tour->beschreibung)
                    <div class="text-muted small">{{ Str::limit($tour->beschreibung, 40) }}</div>
                    @endif
                  </div>
                  @if($tour->aktiv)
                  <span class="badge bg-success-subtle text-success border small">Aktiv</span>
                  @endif
                  <button type="button" class="btn btn-sm btn-outline-primary add-tour" data-tour-id="{{ $tour->id }}">
                    <i class="bi bi-arrow-right"></i>
                  </button>
                </div>
              </div>
              @empty
              <div class="text-center text-muted py-4">
                <i class="bi bi-check-circle fs-3"></i>
                <p class="mb-0 mt-2 small">Alle zugeordnet</p>
              </div>
              @endforelse
            </div>
          </div>
        </div>
      </div>

      {{-- RIGHT: Zugeordnete Touren --}}
      <div class="col-md-6">
        <div class="card border-primary shadow-sm h-100">
          <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
              <i class="bi bi-building"></i> Zugeordnet
              <span class="badge bg-white text-primary ms-2" id="zugeordnet-count">{{ $zugeordnet->count() }}</span>
            </h6>
          </div>
          <div class="card-body p-2" style="max-height: 400px; overflow-y: auto;">
            <div id="zugeordnete-touren" class="tour-list sortable">
              @forelse($zugeordnet as $tour)
              <div class="tour-card assigned" data-tour-id="{{ $tour->id }}" draggable="true">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-grip-vertical text-primary"></i>
                  <span class="badge bg-primary">{{ $loop->iteration }}</span>
                  <div class="flex-grow-1">
                    <div class="fw-semibold small">{{ $tour->name }}</div>
                    @if($tour->beschreibung)
                    <div class="text-muted small">{{ Str::limit($tour->beschreibung, 40) }}</div>
                    @endif
                  </div>
                  @if($tour->aktiv)
                  <span class="badge bg-success-subtle text-success border small">Aktiv</span>
                  @endif
                  <button type="button" class="btn btn-sm btn-outline-danger remove-tour" data-tour-id="{{ $tour->id }}">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
                <input type="hidden" name="tour_ids[]" value="{{ $tour->id }}">
              </div>
              @empty
              <div class="text-center text-muted py-4 drop-zone-hint">
                <i class="bi bi-arrow-left-circle fs-3"></i>
                <p class="mb-0 mt-2 small">Noch keine Touren</p>
              </div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @error('tour_ids')
  <div class="col-12">
    <div class="alert alert-danger py-2 small">
      <i class="bi bi-exclamation-triangle"></i> {{ $message }}
    </div>
  </div>
  @enderror

  @endunless

</div>

@push('styles')
<style>
.tour-list { min-height: 80px; }
.tour-card {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  padding: 8px;
  margin-bottom: 6px;
  cursor: move;
  transition: all 0.2s ease;
}
.tour-card:hover { border-color: #0d6efd; }
.tour-card.assigned { background: #f8f9ff; border-left: 3px solid #0d6efd; }
.tour-card.dragging { opacity: 0.5; }
.drop-zone-hint { border: 2px dashed #dee2e6; border-radius: 6px; }
</style>
@endpush

@push('scripts')
<script>
(function() {
  // ========================================
  // MOBILE Funktionalitaet
  // ========================================
  var zugeordnetMobile = document.getElementById('zugeordnete-touren-mobile');
  var verfuegbarMobile = document.getElementById('verfuegbare-touren-mobile');
  
  if (zugeordnetMobile && verfuegbarMobile) {
    
    // Tour hinzufuegen (Mobile)
    verfuegbarMobile.addEventListener('click', function(e) {
      var btn = e.target.closest('.add-tour-mobile');
      if (!btn) return;
      
      e.preventDefault();
      e.stopPropagation();
      
      var tourId = btn.dataset.tourId;
      var tourName = btn.dataset.tourName || '';
      var tourBeschreibung = btn.dataset.tourBeschreibung || '';
      var item = btn.closest('.tour-item-mobile');
      
      if (!item || !tourId) return;
      
      // Leere Hinweise entfernen
      zugeordnetMobile.querySelectorAll('.empty-hint').forEach(function(el) { el.remove(); });
      
      // Neue Position berechnen
      var position = zugeordnetMobile.querySelectorAll('.tour-item-mobile').length + 1;
      
      // Neues Element fuer zugeordnet erstellen
      var newItem = document.createElement('div');
      newItem.className = 'tour-item-mobile d-flex align-items-center justify-content-between py-2 border-bottom';
      newItem.dataset.tourId = tourId;
      newItem.innerHTML = 
        '<div class="d-flex align-items-center gap-2 flex-grow-1">' +
          '<span class="badge bg-primary tour-position">' + position + '</span>' +
          '<div>' +
            '<div class="fw-semibold small">' + tourName + '</div>' +
            (tourBeschreibung ? '<div class="text-muted small">' + tourBeschreibung + '</div>' : '') +
          '</div>' +
        '</div>' +
        '<button type="button" class="btn btn-sm btn-outline-danger remove-tour-mobile ms-2" ' +
          'data-tour-id="' + tourId + '" data-tour-name="' + tourName + '" data-tour-beschreibung="' + tourBeschreibung + '">' +
          '<i class="bi bi-x-lg"></i>' +
        '</button>' +
        '<input type="hidden" name="tour_ids[]" value="' + tourId + '">';
      
      // Altes Element entfernen, neues hinzufuegen
      item.remove();
      zugeordnetMobile.appendChild(newItem);
      
      // Verfuegbar leer? Hinweis zeigen
      if (verfuegbarMobile.querySelectorAll('.tour-item-mobile').length === 0) {
        verfuegbarMobile.innerHTML = '<div class="text-center text-muted py-3 small empty-hint"><i class="bi bi-check-circle"></i> Alle Touren zugeordnet</div>';
      }
      
      updateMobileCounts();
    });
    
    // Tour entfernen (Mobile)
    zugeordnetMobile.addEventListener('click', function(e) {
      var btn = e.target.closest('.remove-tour-mobile');
      if (!btn) return;
      
      e.preventDefault();
      e.stopPropagation();
      
      var tourId = btn.dataset.tourId;
      var tourName = btn.dataset.tourName || '';
      var tourBeschreibung = btn.dataset.tourBeschreibung || '';
      var item = btn.closest('.tour-item-mobile');
      
      if (!item || !tourId) return;
      
      // Leere Hinweise entfernen
      verfuegbarMobile.querySelectorAll('.empty-hint').forEach(function(el) { el.remove(); });
      
      // Neues Element fuer verfuegbar erstellen
      var newItem = document.createElement('div');
      newItem.className = 'tour-item-mobile d-flex align-items-center justify-content-between py-2 border-bottom';
      newItem.dataset.tourId = tourId;
      newItem.innerHTML = 
        '<div class="flex-grow-1">' +
          '<div class="fw-semibold small">' + tourName + '</div>' +
          (tourBeschreibung ? '<div class="text-muted small">' + tourBeschreibung + '</div>' : '') +
        '</div>' +
        '<button type="button" class="btn btn-sm btn-outline-primary add-tour-mobile ms-2" ' +
          'data-tour-id="' + tourId + '" data-tour-name="' + tourName + '" data-tour-beschreibung="' + tourBeschreibung + '">' +
          '<i class="bi bi-plus-lg"></i>' +
        '</button>';
      
      // Altes Element entfernen, neues hinzufuegen
      item.remove();
      verfuegbarMobile.appendChild(newItem);
      
      // Zugeordnet leer? Hinweis zeigen
      if (zugeordnetMobile.querySelectorAll('.tour-item-mobile').length === 0) {
        zugeordnetMobile.innerHTML = '<div class="text-center text-muted py-3 small empty-hint"><i class="bi bi-inbox"></i> Keine Touren zugeordnet</div>';
      }
      
      updateMobileCounts();
      updateMobilePositions();
    });
    
    function updateMobileCounts() {
      var zCount = document.getElementById('zugeordnet-count-mobile');
      var vCount = document.getElementById('verfuegbar-count-mobile');
      if (zCount) zCount.textContent = zugeordnetMobile.querySelectorAll('.tour-item-mobile').length;
      if (vCount) vCount.textContent = verfuegbarMobile.querySelectorAll('.tour-item-mobile').length;
    }
    
    function updateMobilePositions() {
      zugeordnetMobile.querySelectorAll('.tour-item-mobile').forEach(function(item, index) {
        var badge = item.querySelector('.tour-position');
        if (badge) badge.textContent = index + 1;
      });
    }
  }
  
  // ========================================
  // DESKTOP Drag & Drop
  // ========================================
  var verfuegbarContainer = document.getElementById('verfuegbare-touren');
  var zugeordnetContainer = document.getElementById('zugeordnete-touren');
  
  if (!verfuegbarContainer || !zugeordnetContainer) return;
  
  function updateCounts() {
    var vCount = document.getElementById('verfuegbar-count');
    var zCount = document.getElementById('zugeordnet-count');
    if (vCount) vCount.textContent = verfuegbarContainer.querySelectorAll('.tour-card').length;
    if (zCount) zCount.textContent = zugeordnetContainer.querySelectorAll('.tour-card').length;
  }
  
  function updatePositionNumbers() {
    zugeordnetContainer.querySelectorAll('.tour-card').forEach(function(card, i) {
      var badge = card.querySelector('.badge.bg-primary');
      if (badge) badge.textContent = i + 1;
    });
  }
  
  // Button-Clicks
  document.querySelectorAll('.add-tour').forEach(function(btn) {
    btn.onclick = function(e) {
      e.preventDefault();
      var card = this.closest('.tour-card');
      if (!card) return;
      
      card.classList.add('assigned');
      this.className = 'btn btn-sm btn-outline-danger remove-tour';
      this.innerHTML = '<i class="bi bi-x-lg"></i>';
      
      // Badge hinzufuegen
      var grip = card.querySelector('.bi-grip-vertical');
      if (grip && !card.querySelector('.badge.bg-primary')) {
        grip.classList.replace('text-muted', 'text-primary');
        var badge = document.createElement('span');
        badge.className = 'badge bg-primary';
        badge.textContent = '1';
        grip.after(badge);
      }
      
      // Hidden input
      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'tour_ids[]';
      hidden.value = this.dataset.tourId;
      card.appendChild(hidden);
      
      zugeordnetContainer.querySelector('.drop-zone-hint')?.remove();
      zugeordnetContainer.appendChild(card);
      
      updateCounts();
      updatePositionNumbers();
      attachButtonHandlers();
    };
  });
  
  function attachButtonHandlers() {
    document.querySelectorAll('.remove-tour').forEach(function(btn) {
      btn.onclick = function(e) {
        e.preventDefault();
        var card = this.closest('.tour-card');
        if (!card) return;
        
        card.classList.remove('assigned');
        this.className = 'btn btn-sm btn-outline-primary add-tour';
        this.innerHTML = '<i class="bi bi-arrow-right"></i>';
        
        var grip = card.querySelector('.bi-grip-vertical');
        if (grip) grip.classList.replace('text-primary', 'text-muted');
        card.querySelector('.badge.bg-primary')?.remove();
        card.querySelector('input[type="hidden"]')?.remove();
        
        verfuegbarContainer.appendChild(card);
        
        updateCounts();
        updatePositionNumbers();
        attachButtonHandlers();
      };
    });
  }
  
  attachButtonHandlers();
  updateCounts();
})();
</script>
@endpush
