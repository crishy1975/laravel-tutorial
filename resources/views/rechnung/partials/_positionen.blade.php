{{-- resources/views/rechnung/partials/_positionen.blade.php --}}
{{-- ⭐ MOBILE-OPTIMIERTE VERSION --}}

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
      
      {{-- Positionen-Card --}}
      <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
          <h6 class="mb-0"><i class="bi bi-list-ul"></i> <span class="d-none d-sm-inline">Rechnungs</span>positionen</h6>
          @if(!$readonly)
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPositionModal">
              <i class="bi bi-plus-circle"></i> <span class="d-none d-sm-inline">Position</span>
            </button>
          @endif
        </div>
        
        {{-- ⭐ DESKTOP: Tabelle --}}
        <div class="table-responsive d-none d-lg-block">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th width="50">Pos.</th>
                <th>Beschreibung</th>
                <th width="80" class="text-end">Anzahl</th>
                <th width="60">Einh.</th>
                <th width="100" class="text-end">Einzelpr.</th>
                <th width="60" class="text-end">MwSt</th>
                <th width="100" class="text-end">Netto</th>
                <th width="90" class="text-end">MwSt €</th>
                <th width="100" class="text-end">Brutto</th>
                @if(!$readonly)
                  <th width="90" class="text-center">Akt.</th>
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
            
            {{-- Summen-Footer (Desktop) --}}
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

        {{-- ⭐ MOBILE: Card-Layout --}}
        <div class="d-lg-none">
          @forelse($rechnung->positionen as $pos)
            <div class="border-bottom p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <span class="badge bg-secondary me-2">Pos. {{ $pos->position }}</span>
                  @if($pos->artikelGebaeude)
                    <small class="text-muted">
                      <i class="bi bi-link-45deg"></i> #{{ $pos->artikel_gebaeude_id }}
                    </small>
                  @endif
                </div>
                @if(!$readonly)
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-primary btn-sm" 
                          data-bs-toggle="modal" 
                          data-bs-target="#editPositionModal{{ $pos->id }}">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form action="{{ route('rechnung.position.destroy', $pos->id) }}" 
                        method="POST" 
                        class="d-inline"
                        onsubmit="return confirm('Position wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
                @endif
              </div>
              
              <p class="mb-2 small">{{ $pos->beschreibung }}</p>
              
              <div class="row g-2 small">
                <div class="col-4">
                  <span class="text-muted d-block">Anzahl</span>
                  <strong>{{ number_format($pos->anzahl, 2, ',', '.') }} {{ $pos->einheit }}</strong>
                </div>
                <div class="col-4">
                  <span class="text-muted d-block">Einzelpr.</span>
                  <strong>{{ number_format($pos->einzelpreis, 2, ',', '.') }} €</strong>
                </div>
                <div class="col-4">
                  <span class="text-muted d-block">MwSt</span>
                  <strong>{{ number_format($pos->mwst_satz, 0) }}%</strong>
                </div>
              </div>
              
              <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                <span>Brutto:</span>
                <strong class="text-primary fs-5">{{ number_format($pos->brutto_gesamt, 2, ',', '.') }} €</strong>
              </div>
            </div>

            {{-- Edit Modal für diese Position (auch für Mobile) --}}
            @if(!$readonly)
              @include('rechnung.partials._position_edit_modal', ['position' => $pos])
            @endif

          @empty
            <div class="text-center text-muted py-4">
              <i class="bi bi-inbox" style="font-size: 2rem;"></i>
              <p class="mt-2 mb-0">Noch keine Positionen vorhanden.</p>
            </div>
          @endforelse

          {{-- Mobile Summen --}}
          @if($rechnung->positionen->isNotEmpty())
            <div class="bg-light p-3">
              <div class="d-flex justify-content-between mb-2">
                <span>Netto-Summe:</span>
                <strong>{{ number_format($rechnung->netto_summe, 2, ',', '.') }} €</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>MwSt:</span>
                <strong>{{ number_format($rechnung->mwst_betrag, 2, ',', '.') }} €</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Brutto-Summe:</span>
                <strong>{{ number_format($rechnung->brutto_summe, 2, ',', '.') }} €</strong>
              </div>
              @if($rechnung->ritenuta && $rechnung->ritenuta_betrag > 0)
              <div class="d-flex justify-content-between mb-2 text-warning">
                <span><i class="bi bi-dash-circle"></i> Ritenuta ({{ $rechnung->ritenuta_prozent }}%):</span>
                <strong>- {{ number_format($rechnung->ritenuta_betrag, 2, ',', '.') }} €</strong>
              </div>
              @endif
              <hr class="my-2">
              <div class="d-flex justify-content-between fs-5">
                <span class="fw-bold"><i class="bi bi-cash-coin"></i> Zahlbar:</span>
                <strong class="text-success">{{ number_format($rechnung->zahlbar_betrag, 2, ',', '.') }} €</strong>
              </div>
            </div>
          @endif
        </div>
      </div>

      {{-- ⭐ INFO-BOXEN FÜR REVERSE CHARGE & SPLIT PAYMENT --}}
      @if($rechnung->reverse_charge)
        <div class="alert alert-warning mt-3">
          <i class="bi bi-exclamation-triangle"></i>
          <strong>Reverse Charge aktiv:</strong> 
          <span class="d-none d-md-inline">Umkehrung der Steuerschuldnerschaft (Art. 17 DPR 633/72).</span>
          Die MwSt ist vom Leistungsempfänger zu entrichten.
        </div>
      @endif

      @if($rechnung->split_payment)
        <div class="alert alert-info mt-3">
          <i class="bi bi-info-circle"></i>
          <strong>Split Payment aktiv:</strong> 
          <span class="d-none d-md-inline">Die MwSt wird separat behandelt und</span>
          <span class="d-md-none">MwSt</span> direkt vom öffentlichen Auftraggeber an das Finanzamt abgeführt.
        </div>
      @endif

    @endif

  </div>
</div>

{{-- Modal: Neue Position hinzufügen --}}
@if($rechnung->exists && !$readonly)
  <div class="modal fade" id="addPositionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form action="{{ route('rechnung.position.store', $rechnung->id) }}" method="POST">
          @csrf
          
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Neue Position</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="row g-3">
              
              <div class="col-12">
                <label class="form-label">Beschreibung *</label>
                <textarea name="beschreibung" class="form-control" rows="2" required></textarea>
              </div>

              <div class="col-6 col-md-4">
                <label class="form-label">Anzahl *</label>
                <input type="number" name="anzahl" class="form-control" step="0.01" value="1.00" required inputmode="decimal">
              </div>

              <div class="col-6 col-md-4">
                <label class="form-label">Einheit</label>
                <input type="text" name="einheit" class="form-control" value="Stk" maxlength="10">
              </div>

              <div class="col-6 col-md-4">
                <label class="form-label">Einzelpreis (€) *</label>
                <input type="number" name="einzelpreis" class="form-control" step="0.01" value="0.00" required inputmode="decimal">
              </div>

              <div class="col-6 col-md-4">
                <label class="form-label">MwSt-Satz (%) *</label>
                <input type="number" name="mwst_satz" class="form-control" step="0.01" value="{{ $rechnung->mwst_satz ?? 22.00 }}" required inputmode="decimal">
              </div>

              <div class="col-12 col-md-8">
                <label class="form-label">Position <small class="text-muted">(leer = automatisch)</small></label>
                <input type="number" name="position" class="form-control" min="1" placeholder="z.B. 1, 2, 3..." inputmode="numeric">
              </div>

            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save"></i> Speichern
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endif
