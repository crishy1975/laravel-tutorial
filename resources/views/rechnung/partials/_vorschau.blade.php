{{-- resources/views/rechnung/partials/_vorschau.blade.php --}}

<div class="row g-4">

  {{-- Rechnungs-Header --}}
  <div class="col-12">
    <div class="card bg-light border-0 shadow-sm">
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h5 class="mb-3">
              <i class="bi bi-receipt"></i> 
              @if($rechnung->exists && $rechnung->rechnungsnummer)
                @if($rechnung->typ_rechnung === 'gutschrift')
                  Gutschrift {{ $rechnung->rechnungsnummer }}
                @else
                  Rechnung {{ $rechnung->rechnungsnummer }}
                @endif
              @else
                @if($rechnung->typ_rechnung === 'gutschrift')
                  Gutschrift-Vorschau
                @else
                  Rechnungsvorschau
                @endif
              @endif
            </h5>
            
            <p class="mb-1"><strong>Typ:</strong> {{ $rechnung->typ_rechnung === 'gutschrift' ? 'Gutschrift' : 'Rechnung' }}</p>
            <p class="mb-1"><strong>Rechnungsdatum:</strong> {{ $rechnung->rechnungsdatum?->format('d.m.Y') ?? '-' }}</p>
            <p class="mb-1"><strong>Leistungsdaten:</strong> {{ $rechnung->leistungsdaten ?? '-' }}</p>
            
            {{-- ⭐ NEU: Zahlungsbedingungen --}}
            @if($rechnung->zahlungsbedingungen)
            <p class="mb-1">
              <strong>Zahlungsbedingungen:</strong> 
              {!! $rechnung->zahlungsbedingungen_badge !!}
            </p>
            @endif
            
            <p class="mb-1"><strong>Zahlungsziel:</strong> {{ $rechnung->zahlungsziel?->format('d.m.Y') ?? '-' }}</p>
            
            {{-- ⭐ NEU: Fälligkeitsstatus --}}
            @if($rechnung->faelligkeitsdatum && !$rechnung->istAlsBezahltMarkiert())
            <p class="mb-1">
              <strong>Fälligkeit:</strong> 
              {!! $rechnung->faelligkeits_status_badge !!}
            </p>
            @endif
            
            {{-- ⭐ NEU: Bezahlt-Status --}}
            @if($rechnung->bezahlt_am)
            <p class="mb-1">
              <strong>Bezahlt am:</strong> 
              {{ $rechnung->bezahlt_am->format('d.m.Y') }}
              <span class="badge bg-success ms-2">✓ Bezahlt</span>
            </p>
            @endif
            
            <p class="mb-0"><strong>Status:</strong> {!! $rechnung->status_badge ?? '<span class="badge bg-secondary">Entwurf</span>' !!}</p>
          </div>
          <div class="col-md-6 text-md-end">
            @if($rechnung->exists && $rechnung->pdf_pfad)
              <a href="{{ asset('storage/' . $rechnung->pdf_pfad) }}" 
                 class="btn btn-outline-danger" target="_blank">
                <i class="bi bi-file-pdf"></i> PDF öffnen
              </a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ⭐ NEU: Zahlungsinformationen (prominente Box) - nur wenn Rechnung existiert --}}
  @if($rechnung->exists && $rechnung->zahlungsbedingungen)
  <div class="col-12">
    <div class="card border-{{ $rechnung->istUeberfaellig() ? 'danger' : ($rechnung->istAlsBezahltMarkiert() ? 'success' : 'warning') }}">
      <div class="card-header bg-{{ $rechnung->istUeberfaellig() ? 'danger' : ($rechnung->istAlsBezahltMarkiert() ? 'success' : 'warning') }} text-white">
        <h6 class="mb-0">
          <i class="bi bi-{{ $rechnung->istAlsBezahltMarkiert() ? 'check-circle' : 'calendar-check' }}"></i> 
          Zahlungsinformationen
        </h6>
      </div>
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-8">
            <div class="row g-3">
              <div class="col-md-4">
                <strong>Zahlungsbedingungen:</strong><br>
                {!! $rechnung->zahlungsbedingungen_badge !!}
                <br><small class="text-muted">{{ $rechnung->zahlungsbedingungen_label }}</small>
              </div>
              
              @if($rechnung->zahlungsziel)
              <div class="col-md-4">
                <strong>Zahlungsziel:</strong><br>
                {{ $rechnung->zahlungsziel->format('d.m.Y') }}
                @if(!$rechnung->istAlsBezahltMarkiert())
                  <br><small class="text-muted">
                    @if($rechnung->tage_bis_faelligkeit > 0)
                      <i class="bi bi-hourglass-split"></i> noch {{ $rechnung->tage_bis_faelligkeit }} Tage
                    @elseif($rechnung->tage_bis_faelligkeit < 0)
                      <i class="bi bi-exclamation-triangle text-danger"></i> {{ abs($rechnung->tage_bis_faelligkeit) }} Tage überfällig
                    @else
                      <i class="bi bi-clock-history"></i> heute fällig
                    @endif
                  </small>
                @endif
              </div>
              @endif

              @if($rechnung->bezahlt_am)
              <div class="col-md-4">
                <strong>Bezahlt am:</strong><br>
                {{ $rechnung->bezahlt_am->format('d.m.Y') }}
                <br><span class="badge bg-success">✓ Bezahlt</span>
              </div>
              @endif
            </div>
          </div>
          <div class="col-md-4 text-md-end">
            {!! $rechnung->faelligkeits_status_badge !!}
          </div>
        </div>

        {{-- ⭐ Überfällig-Warnung --}}
        @if($rechnung->istUeberfaellig())
        <div class="alert alert-danger mt-3 mb-0">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <strong>Achtung!</strong> Diese Rechnung ist seit <strong>{{ abs($rechnung->tage_bis_faelligkeit) }} Tagen</strong> überfällig!
        </div>
        @endif
      </div>
    </div>
  </div>
  @endif

  {{-- Adressen nebeneinander --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-person-circle"></i> Rechnungsempfänger</h6>
      </div>
      <div class="card-body">
        <address class="mb-0">
          <strong>{{ $rechnung->re_name ?: '(noch nicht angegeben)' }}</strong><br>
          @if($rechnung->re_strasse)
            {{ $rechnung->re_strasse }} {{ $rechnung->re_hausnummer }}<br>
          @endif
          @if($rechnung->re_plz || $rechnung->re_wohnort)
            {{ $rechnung->re_plz }} {{ $rechnung->re_wohnort }}
            @if($rechnung->re_provinz)
              ({{ $rechnung->re_provinz }})
            @endif
            <br>
          @endif
          @if($rechnung->re_land)
            {{ $rechnung->re_land }}<br>
          @endif
        </address>

        @if($rechnung->re_steuernummer || $rechnung->re_mwst_nummer)
          <hr class="my-3">
          <div class="small text-muted">
            @if($rechnung->re_steuernummer)
              <div><strong>Steuernr.:</strong> {{ $rechnung->re_steuernummer }}</div>
            @endif
            @if($rechnung->re_mwst_nummer)
              <div><strong>MwSt-Nr.:</strong> {{ $rechnung->re_mwst_nummer }}</div>
            @endif
            @if($rechnung->re_codice_univoco)
              <div><strong>Codice Univoco:</strong> {{ $rechnung->re_codice_univoco }}</div>
            @endif
            @if($rechnung->re_pec)
              <div><strong>PEC:</strong> {{ $rechnung->re_pec }}</div>
            @endif
          </div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-info text-white">
        <h6 class="mb-0"><i class="bi bi-envelope"></i> Postadresse</h6>
      </div>
      <div class="card-body">
        <address class="mb-0">
          <strong>{{ $rechnung->post_name ?: '(noch nicht angegeben)' }}</strong><br>
          @if($rechnung->post_strasse)
            {{ $rechnung->post_strasse }} {{ $rechnung->post_hausnummer }}<br>
          @endif
          @if($rechnung->post_plz || $rechnung->post_wohnort)
            {{ $rechnung->post_plz }} {{ $rechnung->post_wohnort }}
            @if($rechnung->post_provinz)
              ({{ $rechnung->post_provinz }})
            @endif
            <br>
          @endif
          @if($rechnung->post_land)
            {{ $rechnung->post_land }}<br>
          @endif
        </address>

        @if($rechnung->post_email || $rechnung->post_pec)
          <hr class="my-3">
          <div class="small text-muted">
            @if($rechnung->post_email)
              <div><strong>E-Mail:</strong> {{ $rechnung->post_email }}</div>
            @endif
            @if($rechnung->post_pec)
              <div><strong>PEC:</strong> {{ $rechnung->post_pec }}</div>
            @endif
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Gebäude --}}
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-secondary text-white">
        <h6 class="mb-0"><i class="bi bi-building"></i> Gebäude</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <strong>Codex:</strong> {{ $rechnung->geb_codex ?: '-' }}
          </div>
          <div class="col-md-4">
            <strong>Name:</strong> {{ $rechnung->geb_name ?: '-' }}
          </div>
          <div class="col-md-4">
            <strong>Adresse:</strong> {{ $rechnung->geb_adresse ?: '-' }}
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Positionen --}}
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-dark text-white">
        <h6 class="mb-0"><i class="bi bi-list-ul"></i> Positionen</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th width="50">Pos.</th>
              <th>Beschreibung</th>
              <th width="100" class="text-end">Menge</th>
              <th width="100" class="text-end">Einzelpreis</th>
              <th width="80" class="text-end">MwSt</th>
              <th width="120" class="text-end">Gesamt</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rechnung->positionen as $pos)
              <tr>
                <td class="text-muted">{{ $pos->position }}</td>
                <td>{{ $pos->beschreibung }}</td>
                <td class="text-end">{{ number_format($pos->anzahl, 2, ',', '.') }} {{ $pos->einheit }}</td>
                <td class="text-end">{{ number_format($pos->einzelpreis, 2, ',', '.') }} €</td>
                <td class="text-end">{{ number_format($pos->mwst_satz, 0) }}%</td>
                <td class="text-end"><strong>{{ number_format($pos->netto_gesamt, 2, ',', '.') }} €</strong></td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-3">
                  Noch keine Positionen vorhanden.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Beträge --}}
  <div class="col-lg-6 offset-lg-6">
    <div class="card border-primary">
      <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-calculator"></i> Zusammenfassung</h6>
      </div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tr>
            <td><strong>Netto-Summe:</strong></td>
            <td class="text-end">{{ number_format($rechnung->netto_summe ?? 0, 2, ',', '.') }} €</td>
          </tr>
          <tr>
            <td><strong>MwSt ({{ number_format($rechnung->mwst_satz ?? 0, 0) }}%):</strong></td>
            <td class="text-end">{{ number_format($rechnung->mwst_betrag ?? 0, 2, ',', '.') }} €</td>
          </tr>
          <tr class="table-light">
            <td><strong>Brutto-Summe:</strong></td>
            <td class="text-end"><strong>{{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }} €</strong></td>
          </tr>
          
          @if($rechnung->ritenuta && $rechnung->ritenuta_betrag > 0)
            <tr class="table-warning">
              <td>
                <i class="bi bi-dash-circle"></i> 
                <strong>Ritenuta ({{ number_format($rechnung->ritenuta_prozent, 2) }}%):</strong>
              </td>
              <td class="text-end">- {{ number_format($rechnung->ritenuta_betrag, 2, ',', '.') }} €</td>
            </tr>
          @endif
          
          <tr class="table-success">
            <td class="fs-5"><strong><i class="bi bi-cash-coin"></i> Zahlbar:</strong></td>
            <td class="text-end fs-5"><strong>{{ number_format($rechnung->zahlbar_betrag ?? 0, 2, ',', '.') }} €</strong></td>
          </tr>
        </table>

        @if($rechnung->split_payment)
          <div class="alert alert-info py-2 mt-3 mb-0">
            <small><i class="bi bi-info-circle"></i> <strong>Split Payment</strong> aktiv</small>
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- FatturaPA --}}
  @if($rechnung->cup || $rechnung->cig || $rechnung->codice_commessa || $rechnung->auftrag_id)
    <div class="col-12">
      <div class="card border-warning">
        <div class="card-header bg-warning">
          <h6 class="mb-0"><i class="bi bi-file-earmark-text"></i> FatturaPA-Daten</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            @if($rechnung->cup)
              <div class="col-md-3">
                <strong>CUP:</strong> {{ $rechnung->cup }}
              </div>
            @endif
            @if($rechnung->cig)
              <div class="col-md-3">
                <strong>CIG:</strong> {{ $rechnung->cig }}
              </div>
            @endif
            @if($rechnung->codice_commessa)
              <div class="col-md-3">
                <strong>Codice Commessa:</strong> {{ $rechnung->codice_commessa }}
              </div>
            @endif
            @if($rechnung->auftrag_id)
              <div class="col-md-3">
                <strong>Auftrags-ID:</strong> {{ $rechnung->auftrag_id }}
              </div>
            @endif
            @if($rechnung->auftrag_datum)
              <div class="col-md-3">
                <strong>Auftrags-Datum:</strong> {{ $rechnung->auftrag_datum->format('d.m.Y') }}
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif

  {{-- Bemerkungen --}}
  @if($rechnung->bemerkung_kunde || $rechnung->bemerkung)
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-light">
          <h6 class="mb-0"><i class="bi bi-chat-left-text"></i> Bemerkungen</h6>
        </div>
        <div class="card-body">
          @if($rechnung->bemerkung_kunde)
            <div class="mb-3">
              <strong class="text-primary">Für Kunde sichtbar:</strong>
              <p class="mb-0 mt-1">{{ $rechnung->bemerkung_kunde }}</p>
            </div>
          @endif
          @if($rechnung->bemerkung)
            <div>
              <strong class="text-muted">Intern:</strong>
              <p class="mb-0 mt-1 text-muted">{{ $rechnung->bemerkung }}</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  @endif

</div>