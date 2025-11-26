{{-- resources/views/gebaeude/partials/_touren.blade.php --}}
{{-- Modernes Two-Panel Design mit Drag & Drop für Touren-Zuordnung --}}

@php
$hasId = isset($gebaeude) && $gebaeude?->exists;
// Zugeordnete Touren (mit Reihenfolge aus Pivot)
$zugeordnet = $hasId ? $gebaeude->touren : collect();
$zugeordnetIds = $zugeordnet->pluck('id')->toArray();
// Verfügbare Touren (nicht zugeordnet)
$verfuegbar = ($tourenAlle ?? collect())->reject(fn($t) => in_array($t->id, $zugeordnetIds));
@endphp

<div class="row g-4">
  
  {{-- Header --}}
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-map text-primary"></i>
        <h5 class="mb-0">Touren-Zuordnung</h5>
      </div>
      @if($hasId)
      <div class="text-muted small">
        <i class="bi bi-info-circle"></i>
        Ziehe Touren per Drag & Drop • Reihenfolge ändern durch Verschieben
      </div>
      @endif
    </div>
    <hr class="mt-2 mb-0">
  </div>

  @unless($hasId)
  <div class="col-12">
    <div class="alert alert-info mb-0">
      <i class="bi bi-info-circle"></i>
      Touren können erst nach dem Speichern des Gebäudes zugeordnet werden.
    </div>
  </div>
  @else

  {{-- Two-Panel Layout --}}
  <div class="col-12">
    <div class="row g-3">
      
      {{-- LEFT: Verfügbare Touren --}}
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-light">
            <h6 class="mb-0">
              <i class="bi bi-inbox"></i>
              Verfügbare Touren
              <span class="badge bg-secondary ms-2" id="verfuegbar-count">{{ $verfuegbar->count() }}</span>
            </h6>
          </div>
          <div class="card-body p-2" style="max-height: 500px; overflow-y: auto;">
            <div id="verfuegbare-touren" class="tour-list">
              @forelse($verfuegbar as $tour)
              <div class="tour-card" data-tour-id="{{ $tour->id }}" draggable="true">
                <div class="d-flex align-items-center gap-3">
                  <div class="drag-handle">
                    <i class="bi bi-grip-vertical text-muted"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold">{{ $tour->name }}</div>
                    @if($tour->beschreibung)
                    <div class="text-muted small">{{ Str::limit($tour->beschreibung, 50) }}</div>
                    @endif
                  </div>
                  <div class="tour-status">
                    @if($tour->aktiv)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      <i class="bi bi-check-circle"></i> Aktiv
                    </span>
                    @else
                    <span class="badge bg-secondary-subtle text-secondary border">
                      <i class="bi bi-pause-circle"></i> Inaktiv
                    </span>
                    @endif
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-primary add-tour" 
                          data-tour-id="{{ $tour->id }}">
                    <i class="bi bi-arrow-right"></i>
                  </button>
                </div>
              </div>
              @empty
              <div class="text-center text-muted py-4">
                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                <p class="mb-0 mt-2">Alle Touren zugeordnet</p>
              </div>
              @endforelse
            </div>
          </div>
        </div>
      </div>

      {{-- RIGHT: Zugeordnete Touren (sortierbar) --}}
      <div class="col-md-6">
        <div class="card border-primary shadow-sm h-100">
          <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
              <i class="bi bi-building"></i>
              Zugeordnete Touren ({{ $gebaeude->gebaeude_name ?? 'Gebäude' }})
              <span class="badge bg-white text-primary ms-2" id="zugeordnet-count">{{ $zugeordnet->count() }}</span>
            </h6>
          </div>
          <div class="card-body p-2" style="max-height: 500px; overflow-y: auto;">
            <div id="zugeordnete-touren" class="tour-list sortable">
              @forelse($zugeordnet as $tour)
              <div class="tour-card assigned" data-tour-id="{{ $tour->id }}" draggable="true">
                <div class="d-flex align-items-center gap-3">
                  <div class="drag-handle">
                    <i class="bi bi-grip-vertical text-primary"></i>
                  </div>
                  <div class="position-number">
                    <span class="badge bg-primary">{{ $loop->iteration }}</span>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold">{{ $tour->name }}</div>
                    @if($tour->beschreibung)
                    <div class="text-muted small">{{ Str::limit($tour->beschreibung, 50) }}</div>
                    @endif
                  </div>
                  <div class="tour-status">
                    @if($tour->aktiv)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      <i class="bi bi-check-circle"></i> Aktiv
                    </span>
                    @else
                    <span class="badge bg-secondary-subtle text-secondary border">
                      <i class="bi bi-pause-circle"></i> Inaktiv
                    </span>
                    @endif
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-danger remove-tour" 
                          data-tour-id="{{ $tour->id }}">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
                {{-- Hidden Input für Form-Submit --}}
                <input type="hidden" name="tour_ids[]" value="{{ $tour->id }}">
              </div>
              @empty
              <div class="text-center text-muted py-4 drop-zone-hint">
                <i class="bi bi-arrow-left-circle" style="font-size: 2rem;"></i>
                <p class="mb-0 mt-2">Noch keine Touren zugeordnet</p>
                <small>Ziehe Touren hierher oder klicke auf <i class="bi bi-arrow-right"></i></small>
              </div>
              @endforelse
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  {{-- Validierungsfehler --}}
  <div class="col-12">
    @error('tour_ids')
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-triangle"></i> {{ $message }}
    </div>
    @enderror
  </div>

  @endunless

</div>

{{-- CSS Styles --}}
@push('styles')
<style>
.tour-list {
  min-height: 100px;
}

.tour-card {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 8px;
  cursor: move;
  transition: all 0.2s ease;
}

.tour-card:hover {
  border-color: #0d6efd;
  box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
  transform: translateY(-1px);
}

.tour-card.assigned {
  background: #f8f9ff;
  border-left: 3px solid #0d6efd;
}

.tour-card.dragging {
  opacity: 0.5;
  transform: rotate(2deg);
}

.tour-card.drag-over {
  border: 2px dashed #0d6efd;
  background: #e7f3ff;
}

.drag-handle {
  cursor: grab;
  font-size: 1.2rem;
}

.drag-handle:active {
  cursor: grabbing;
}

.position-number {
  min-width: 30px;
  text-align: center;
}

.drop-zone-hint {
  border: 2px dashed #dee2e6;
  border-radius: 8px;
  padding: 20px;
}

/* Hover-Effekte für Buttons */
.add-tour, .remove-tour {
  opacity: 0.7;
  transition: opacity 0.2s;
}

.tour-card:hover .add-tour,
.tour-card:hover .remove-tour {
  opacity: 1;
}

/* Sortierbare Liste */
.sortable .tour-card {
  cursor: move;
}

/* Mobile Optimierung */
@media (max-width: 768px) {
  .tour-card {
    padding: 8px;
  }
  
  .tour-card .small {
    display: none;
  }
}
</style>
@endpush

{{-- JavaScript --}}
@push('scripts')
<script>
(function() {
  'use strict';
  
  const verfuegbarContainer = document.getElementById('verfuegbare-touren');
  const zugeordnetContainer = document.getElementById('zugeordnete-touren');
  const verfuegbarCount = document.getElementById('verfuegbar-count');
  const zugeordnetCount = document.getElementById('zugeordnet-count');
  
  if (!verfuegbarContainer || !zugeordnetContainer) return;
  
  let draggedElement = null;
  let sourceContainer = null;
  
  // ═══════════════════════════════════════════════════════════
  // 🎯 HELPER FUNCTIONS
  // ═══════════════════════════════════════════════════════════
  
  function updateCounts() {
    const verfuegbarCards = verfuegbarContainer.querySelectorAll('.tour-card').length;
    const zugeordnetCards = zugeordnetContainer.querySelectorAll('.tour-card').length;
    
    if (verfuegbarCount) verfuegbarCount.textContent = verfuegbarCards;
    if (zugeordnetCount) zugeordnetCount.textContent = zugeordnetCards;
    
    // Drop-Zone-Hint anzeigen/verbergen
    updateDropZoneHint();
  }
  
  function updateDropZoneHint() {
    const hint = zugeordnetContainer.querySelector('.drop-zone-hint');
    const cards = zugeordnetContainer.querySelectorAll('.tour-card');
    
    if (cards.length === 0 && !hint) {
      zugeordnetContainer.innerHTML = `
        <div class="text-center text-muted py-4 drop-zone-hint">
          <i class="bi bi-arrow-left-circle" style="font-size: 2rem;"></i>
          <p class="mb-0 mt-2">Noch keine Touren zugeordnet</p>
          <small>Ziehe Touren hierher oder klicke auf <i class="bi bi-arrow-right"></i></small>
        </div>
      `;
    } else if (cards.length > 0 && hint) {
      hint.remove();
    }
  }
  
  function updatePositionNumbers() {
    const cards = zugeordnetContainer.querySelectorAll('.tour-card');
    cards.forEach((card, index) => {
      const badge = card.querySelector('.position-number .badge');
      if (badge) badge.textContent = index + 1;
    });
  }
  
  function getTourData(tourId) {
    // Finde Tour in beiden Containern
    const card = document.querySelector(`.tour-card[data-tour-id="${tourId}"]`);
    if (!card) return null;
    
    return {
      id: tourId,
      html: card.outerHTML
    };
  }
  
  // ═══════════════════════════════════════════════════════════
  // 🔄 TOUR HINZUFÜGEN (verfügbar → zugeordnet)
  // ═══════════════════════════════════════════════════════════
  
  function addTourToAssigned(tourId) {
    const sourceCard = verfuegbarContainer.querySelector(`.tour-card[data-tour-id="${tourId}"]`);
    if (!sourceCard) return;
    
    // Klone die Karte
    const newCard = sourceCard.cloneNode(true);
    newCard.classList.add('assigned');
    
    // Ersetze Button: arrow-right → x-lg
    const button = newCard.querySelector('.add-tour');
    if (button) {
      button.classList.remove('btn-outline-primary', 'add-tour');
      button.classList.add('btn-outline-danger', 'remove-tour');
      button.innerHTML = '<i class="bi bi-x-lg"></i>';
    }
    
    // Füge Position-Number hinzu
    const dragHandle = newCard.querySelector('.drag-handle');
    if (dragHandle && !newCard.querySelector('.position-number')) {
      const posNum = document.createElement('div');
      posNum.className = 'position-number';
      posNum.innerHTML = '<span class="badge bg-primary">1</span>';
      dragHandle.after(posNum);
    }
    
    // Füge Hidden Input hinzu
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'tour_ids[]';
    hiddenInput.value = tourId;
    newCard.appendChild(hiddenInput);
    
    // Grip-Vertical Icon ändern
    const gripIcon = newCard.querySelector('.drag-handle i');
    if (gripIcon) {
      gripIcon.classList.remove('text-muted');
      gripIcon.classList.add('text-primary');
    }
    
    // Entferne aus verfügbar
    sourceCard.remove();
    
    // Füge zu zugeordnet hinzu
    zugeordnetContainer.appendChild(newCard);
    
    updateCounts();
    updatePositionNumbers();
    attachEventListeners();
  }
  
  // ═══════════════════════════════════════════════════════════
  // ❌ TOUR ENTFERNEN (zugeordnet → verfügbar)
  // ═══════════════════════════════════════════════════════════
  
  function removeTourFromAssigned(tourId) {
    const sourceCard = zugeordnetContainer.querySelector(`.tour-card[data-tour-id="${tourId}"]`);
    if (!sourceCard) return;
    
    // Klone die Karte
    const newCard = sourceCard.cloneNode(true);
    newCard.classList.remove('assigned');
    
    // Ersetze Button: x-lg → arrow-right
    const button = newCard.querySelector('.remove-tour');
    if (button) {
      button.classList.remove('btn-outline-danger', 'remove-tour');
      button.classList.add('btn-outline-primary', 'add-tour');
      button.innerHTML = '<i class="bi bi-arrow-right"></i>';
    }
    
    // Entferne Position-Number
    const posNum = newCard.querySelector('.position-number');
    if (posNum) posNum.remove();
    
    // Entferne Hidden Input
    const hiddenInput = newCard.querySelector('input[type="hidden"]');
    if (hiddenInput) hiddenInput.remove();
    
    // Grip-Vertical Icon ändern
    const gripIcon = newCard.querySelector('.drag-handle i');
    if (gripIcon) {
      gripIcon.classList.remove('text-primary');
      gripIcon.classList.add('text-muted');
    }
    
    // Entferne aus zugeordnet
    sourceCard.remove();
    
    // Füge zu verfügbar hinzu
    verfuegbarContainer.appendChild(newCard);
    
    updateCounts();
    updatePositionNumbers();
    attachEventListeners();
  }
  
  // ═══════════════════════════════════════════════════════════
  // 🎭 DRAG & DROP
  // ═══════════════════════════════════════════════════════════
  
  function handleDragStart(e) {
    draggedElement = this;
    sourceContainer = this.closest('.tour-list');
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
  }
  
  function handleDragOver(e) {
    if (e.preventDefault) e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    
    const afterElement = getDragAfterElement(this, e.clientY);
    const draggable = document.querySelector('.dragging');
    
    if (afterElement == null) {
      this.appendChild(draggable);
    } else {
      this.insertBefore(draggable, afterElement);
    }
    
    return false;
  }
  
  function handleDragEnter(e) {
    this.classList.add('drag-over');
  }
  
  function handleDragLeave(e) {
    this.classList.remove('drag-over');
  }
  
  function handleDrop(e) {
    if (e.stopPropagation) e.stopPropagation();
    
    const targetContainer = this;
    targetContainer.classList.remove('drag-over');
    
    if (draggedElement && sourceContainer !== targetContainer) {
      const tourId = draggedElement.dataset.tourId;
      
      // Von verfügbar → zugeordnet
      if (sourceContainer === verfuegbarContainer && targetContainer === zugeordnetContainer) {
        addTourToAssigned(tourId);
      }
      // Von zugeordnet → verfügbar
      else if (sourceContainer === zugeordnetContainer && targetContainer === verfuegbarContainer) {
        removeTourFromAssigned(tourId);
      }
    } else {
      // Innerhalb zugeordnet: Reihenfolge ändern
      updatePositionNumbers();
    }
    
    return false;
  }
  
  function handleDragEnd(e) {
    this.classList.remove('dragging');
    
    // Entferne drag-over von allen
    document.querySelectorAll('.tour-list').forEach(container => {
      container.classList.remove('drag-over');
    });
  }
  
  function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.tour-card:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      
      if (offset < 0 && offset > closest.offset) {
        return { offset: offset, element: child };
      } else {
        return closest;
      }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }
  
  // ═══════════════════════════════════════════════════════════
  // 🔗 EVENT LISTENERS
  // ═══════════════════════════════════════════════════════════
  
  function attachEventListeners() {
    // Button-Clicks
    document.querySelectorAll('.add-tour').forEach(btn => {
      btn.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        addTourToAssigned(this.dataset.tourId);
      };
    });
    
    document.querySelectorAll('.remove-tour').forEach(btn => {
      btn.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        removeTourFromAssigned(this.dataset.tourId);
      };
    });
    
    // Drag & Drop für alle Karten
    document.querySelectorAll('.tour-card').forEach(card => {
      card.draggable = true;
      card.ondragstart = handleDragStart;
      card.ondragend = handleDragEnd;
    });
  }
  
  // Container-Events
  [verfuegbarContainer, zugeordnetContainer].forEach(container => {
    container.ondragover = handleDragOver;
    container.ondragenter = handleDragEnter;
    container.ondragleave = handleDragLeave;
    container.ondrop = handleDrop;
  });
  
  // Initiale Listeners
  attachEventListeners();
  updateCounts();
  
})();
</script>
@endpush