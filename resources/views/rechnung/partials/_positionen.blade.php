{{-- resources/views/rechnung/partials/_positionen.blade.php --}}

@php
  $readonly = $rechnung->exists && !$rechnung->ist_editierbar;
@endphp

<div class="row">
  <div class="col-12">
    
    @if(!$rechnung->exists)
      <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Positionen können erst nach dem Anlegen der Rechnung bearbeitet werden.
      </div>
    @else
      
      {{-- Positionen-Tabelle --}}
      <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-list-ul"></i> Rechnungspositionen</h6>
          @if(!$readonly)
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPositionModal">
              <i class="bi bi-plus-circle"></i> Position hinzufügen
            </button>
          @endif
        </div>
        
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th width="50">Pos.</th>
                <th>Beschreibung</th>
                <th width="100" class="text-end">Anzahl</th>
                <th width="80">Einheit</th>
                <th width="120" class="text-end">Einzelpreis</th>
                <th width="80" class="text-end">MwSt %</th>
                <th width="120" class="text-end">Netto</th>
                <th width="120" class="text-end">MwSt</th>
                <th width="120" class="text-end">Brutto</th>
                @if(!$readonly)
                  <th width="100" class="text-center">Aktionen</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @forelse($rechnung->positionen as $pos)
                <tr>
                  <td class="text-muted">{{ $pos->position }}</td>
                  <td>
                    {{ $pos->beschreibung }}
                    @if($pos->artikelGebaeude)
                      <br><small class="text-muted">
                        <i class="bi bi-link-45deg"></i> Artikel #{{ $pos->artikel_gebaeude_id }}
                      </small>
                    @endif
                  </td>
                  <td class="text-end">{{ number_format($pos->anzahl, 2, ',', '.') }}</td>
                  <td>{{ $pos->einheit }}</td>
                  <td class="text-end">{{ number_format($pos->einzelpreis, 2, ',', '.') }} €</td>
                  <td class="text-end">{{ number_format($pos->mwst_satz, 0) }}%</td>
                  <td class="text-end"><strong>{{ number_format($pos->netto_gesamt, 2, ',', '.') }} €</strong></td>
                  <td class="text-end">{{ number_format($pos->mwst_betrag, 2, ',', '.') }} €</td>
                  <td class="text-end"><strong>{{ number_format($pos->brutto_gesamt, 2, ',', '.') }} €</strong></td>
                  @if(!$readonly)
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editPositionModal{{ $pos->id }}"
                                title="Bearbeiten">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('rechnung.position.destroy', $pos->id) }}" 
                              method="POST" 
                              class="d-inline"
                              onsubmit="return confirm('Position wirklich löschen?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-outline-danger" title="Löschen">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  @endif
                </tr>

                {{-- Edit Modal für diese Position --}}
                @if(!$readonly)
                  @include('rechnung.partials._position_edit_modal', ['position' => $pos])
                @endif

              @empty
                <tr>
                  <td colspan="{{ $readonly ? 9 : 10 }}" class="text-center text-muted py-4">
                    <i class="bi bi-inbox"></i> Noch keine Positionen vorhanden.
                  </td>
                </tr>
              @endforelse
            </tbody>
            
            {{-- Summen-Footer --}}
            @if($rechnung->positionen->isNotEmpty())
              <tfoot class="table-light">
                <tr>
                  <td colspan="{{ $readonly ? 6 : 7 }}" class="text-end fw-bold">Summe:</td>
                  <td class="text-end fw-bold">{{ number_format($rechnung->netto_summe, 2, ',', '.') }} €</td>
                  <td class="text-end fw-bold">{{ number_format($rechnung->mwst_betrag, 2, ',', '.') }} €</td>
                  <td class="text-end fw-bold">{{ number_format($rechnung->brutto_summe, 2, ',', '.') }} €</td>
                  @if(!$readonly)
                    <td></td>
                  @endif
                </tr>
                
                @if($rechnung->ritenuta && $rechnung->ritenuta_betrag > 0)
                  <tr class="table-warning">
                    <td colspan="{{ $readonly ? 8 : 9 }}" class="text-end">
                      <i class="bi bi-dash-circle"></i> Ritenuta ({{ $rechnung->ritenuta_prozent }}%):
                    </td>
                    <td class="text-end fw-bold">- {{ number_format($rechnung->ritenuta_betrag, 2, ',', '.') }} €</td>
                    @if(!$readonly)
                      <td></td>
                    @endif
                  </tr>
                @endif
                
                <tr class="table-success">
                  <td colspan="{{ $readonly ? 8 : 9 }}" class="text-end fs-5 fw-bold">
                    <i class="bi bi-cash-coin"></i> Zahlbar:
                  </td>
                  <td class="text-end fs-5 fw-bold">{{ number_format($rechnung->zahlbar_betrag, 2, ',', '.') }} €</td>
                  @if(!$readonly)
                    <td></td>
                  @endif
                </tr>
              </tfoot>
            @endif
          </table>
        </div>
      </div>

      {{-- Info-Box --}}
      @if($rechnung->split_payment)
        <div class="alert alert-info mt-3">
          <i class="bi bi-info-circle"></i>
          <strong>Split Payment aktiv:</strong> Die MwSt wird separat behandelt.
        </div>
      @endif

    @endif

  </div>
</div>

{{-- Modal: Neue Position hinzufügen --}}
@if($rechnung->exists && !$readonly)
  <div class="modal fade" id="addPositionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form action="{{ route('rechnung.position.store', $rechnung->id) }}" method="POST">
          @csrf
          
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Neue Position hinzufügen</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="row g-3">
              
              <div class="col-12">
                <label class="form-label">Beschreibung *</label>
                <textarea name="beschreibung" class="form-control" rows="2" required></textarea>
              </div>

              <div class="col-md-4">
                <label class="form-label">Anzahl *</label>
                <input type="number" name="anzahl" class="form-control" step="0.01" value="1.00" required>
              </div>

              <div class="col-md-4">
                <label class="form-label">Einheit</label>
                <input type="text" name="einheit" class="form-control" value="Stk" maxlength="10">
              </div>

              <div class="col-md-4">
                <label class="form-label">Einzelpreis (€) *</label>
                <input type="number" name="einzelpreis" class="form-control" step="0.01" value="0.00" required>
              </div>

              <div class="col-md-4">
                <label class="form-label">MwSt-Satz (%) *</label>
                <input type="number" name="mwst_satz" class="form-control" step="0.01" value="{{ $rechnung->mwst_satz ?? 22.00 }}" required>
              </div>

              <div class="col-md-8">
                <label class="form-label">Position (leer = automatisch am Ende)</label>
                <input type="number" name="position" class="form-control" min="1" placeholder="z.B. 1, 2, 3...">
              </div>

            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save"></i> Position speichern
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endif