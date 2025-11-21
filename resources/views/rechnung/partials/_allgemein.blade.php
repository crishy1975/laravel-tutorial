{{-- resources/views/rechnung/partials/_allgemein.blade.php --}}

@php
    // Formular ist nur bearbeitbar, wenn Rechnung noch editierbar ist
    $readonly = $rechnung->exists && !$rechnung->ist_editierbar;
@endphp

<div class="row g-4">

    {{-- INFO-CARDS: Gebäude (nur Anzeige) --}}
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-building"></i> Gebäude
                </h6>
            </div>
            <div class="card-body small">
                <div class="mb-1">
                    <strong>Codex:</strong>
                    {{ $rechnung->geb_codex ?: '-' }}
                </div>
                <div class="mb-1">
                    <strong>Name:</strong>
                    {{ $rechnung->geb_name ?: '-' }}
                </div>
                <div class="mb-1">
                    <strong>Adresse:</strong>
                    {{ $rechnung->geb_adresse ?: '-' }}
                </div>

                {{-- Gebäude-Link nur anzeigen, wenn eine passende Route existiert --}}
                @if($rechnung->gebaeude_id && Route::has('gebaeude.show'))
                    <a href="{{ route('gebaeude.show', $rechnung->gebaeude_id) }}"
                       class="btn btn-sm btn-outline-light mt-3">
                        <i class="bi bi-box-arrow-up-right"></i> Gebäude öffnen
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Adresse 1: Rechnungsempfänger (nur Info, editierbar im Tab "Adressen") --}}
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-person-circle"></i> Rechnungsempfänger
                </h6>
            </div>
            <div class="card-body small">
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

                @if($rechnung->re_steuernummer || $rechnung->re_mwst_nummer || $rechnung->re_codice_univoco || $rechnung->re_pec)
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

    {{-- Adresse 2: Postadresse --}}
    <div class="col-xl-4 col-lg-12">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="bi bi-envelope"></i> Postadresse
                </h6>
            </div>
            <div class="card-body small">
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

    {{-- CARD: Rechnungsdaten + Typ + Status --}}
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-receipt-cutoff"></i> Rechnungsdaten
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    {{-- Rechnungsnummer (readonly bei bestehender Rechnung) --}}
                    @if($rechnung->exists)
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text"
                                       class="form-control"
                                       value="{{ $rechnung->nummern }}"
                                       disabled>
                                <label>Rechnungsnummer</label>
                            </div>
                        </div>
                    @endif

                    {{-- Rechnungsdatum --}}
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="date"
                                   name="rechnungsdatum"
                                   class="form-control @error('rechnungsdatum') is-invalid @enderror"
                                   value="{{ old('rechnungsdatum', $rechnung->rechnungsdatum?->toDateString() ?? now()->toDateString()) }}"
                                   {{ $readonly ? 'disabled' : '' }}
                                   required>
                            <label>Rechnungsdatum *</label>
                            @error('rechnungsdatum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Zahlungsziel --}}
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="date"
                                   name="zahlungsziel"
                                   class="form-control @error('zahlungsziel') is-invalid @enderror"
                                   value="{{ old('zahlungsziel', $rechnung->zahlungsziel?->toDateString()) }}"
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label>Zahlungsziel</label>
                            @error('zahlungsziel') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Leistungs- / Rechnungsdaten (als Textfeld) --}}
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="text"
                                   name="leistungsdaten"
                                   class="form-control @error('leistungsdaten') is-invalid @enderror"
                                   value="{{ old('leistungsdaten', $rechnung->leistungsdaten ?? '') }}"
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label>Leistungsdaten (Text)</label>
                            @error('leistungsdaten') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Typ Rechnung (Rechnung / Gutschrift) --}}
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select name="typ_rechnung"
                                    class="form-select @error('typ_rechnung') is-invalid @enderror"
                                    {{ $readonly ? 'disabled' : '' }}>
                                @php
                                    $typ = old('typ_rechnung', $rechnung->typ_rechnung ?? 'rechnung');
                                @endphp
                                <option value="rechnung" {{ $typ === 'rechnung' ? 'selected' : '' }}>
                                    Rechnung
                                </option>
                                <option value="gutschrift" {{ $typ === 'gutschrift' ? 'selected' : '' }}>
                                    Gutschrift
                                </option>
                            </select>
                            <label>Typ</label>
                            @error('typ_rechnung') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select name="status"
                                    class="form-select @error('status') is-invalid @enderror"
                                    {{ $readonly ? 'disabled' : '' }}>
                                <option value="draft" {{ old('status', $rechnung->status) === 'draft' ? 'selected' : '' }}>
                                    Entwurf
                                </option>
                                <option value="sent" {{ old('status', $rechnung->status) === 'sent' ? 'selected' : '' }}>
                                    Versendet
                                </option>
                                <option value="paid" {{ old('status', $rechnung->status) === 'paid' ? 'selected' : '' }}>
                                    Bezahlt
                                </option>
                                <option value="overdue" {{ old('status', $rechnung->status) === 'overdue' ? 'selected' : '' }}>
                                    Überfällig
                                </option>
                                <option value="cancelled" {{ old('status', $rechnung->status) === 'cancelled' ? 'selected' : '' }}>
                                    Storniert
                                </option>
                            </select>
                            <label>Status</label>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Bezahlt am --}}
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="date"
                                   name="bezahlt_am"
                                   class="form-control @error('bezahlt_am') is-invalid @enderror"
                                   value="{{ old('bezahlt_am', $rechnung->bezahlt_am?->toDateString()) }}"
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label>Bezahlt am</label>
                            @error('bezahlt_am') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- CARD: Fattura-Profil + CUP/CIG/ID --}}
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-file-earmark-text"></i> Fattura & Auftrag
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    @php
                        // Vorauswahl für das Fattura-Profil (wie bei Gebäude)
                        $fatturaProfileSel = (string) old('fattura_profile_id', $rechnung->fattura_profile_id ?? '');
                    @endphp

                    {{-- Fattura-Profil --}}
                    <div class="col-12">
                        <div class="form-floating">
                            <select
                                class="form-select @error('fattura_profile_id') is-invalid @enderror"
                                id="fattura_profile_id"
                                name="fattura_profile_id"
                                aria-label="Fattura-Profil"
                                {{ $readonly ? 'disabled' : '' }}>
                                <option value="">– Kein Profil –</option>
                                @foreach(($profile ?? []) as $p)
                                    <option value="{{ $p->id }}"
                                        {{ $fatturaProfileSel === (string) $p->id ? 'selected' : '' }}>
                                        {{ $p->bezeichnung }}
                                    </option>
                                @endforeach
                            </select>
                            <label for="fattura_profile_id">Fattura-Profil</label>
                            @error('fattura_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- 🔎 Dynamische Infozeile zum gewählten Profil (wie in Gebäude) --}}
                        <div id="fattura_profile_info" class="form-text mt-1">
                            {{-- Wird per JS befüllt --}}
                        </div>

                        {{-- 🔒 Datenquelle für JS (sauber serialisiert, keine Inline-Objekte im DOM) --}}
                        <script type="application/json" id="fattura_profiles_data">
                            {!! json_encode(
                                collect($profile ?? [])->map(function($p) {
                                    return [
                                        'id'            => (string) $p->id,
                                        'bezeichnung'   => $p->bezeichnung,
                                        'mwst_satz'     => $p->mwst_satz,      // Zahl oder String, wird im JS formatiert
                                        'split_payment' => (bool) $p->split_payment,
                                        'ritenuta'      => (bool) $p->ritenuta,
                                    ];
                                })->values(),
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            ) !!}
                        </script>
                    </div>

                    {{-- CUP --}}
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input
                                type="text"
                                class="form-control @error('cup') is-invalid @enderror"
                                id="cup"
                                name="cup"
                                placeholder=" "
                                maxlength="20"
                                value="{{ old('cup', $rechnung->cup) }}"
                                {{ $readonly ? 'disabled' : '' }}>
                            <label for="cup">CUP</label>
                            @error('cup') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- CIG --}}
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input
                                type="text"
                                class="form-control @error('cig') is-invalid @enderror"
                                id="cig"
                                name="cig"
                                placeholder=" "
                                maxlength="10"
                                value="{{ old('cig', $rechnung->cig) }}"
                                {{ $readonly ? 'disabled' : '' }}>
                            <label for="cig">CIG</label>
                            @error('cig') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- ID / Auftrags-ID --}}
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input
                                type="text"
                                class="form-control @error('auftrag_id') is-invalid @enderror"
                                id="auftrag_id"
                                name="auftrag_id"
                                placeholder=" "
                                maxlength="50"
                                value="{{ old('auftrag_id', $rechnung->auftrag_id) }}"
                                {{ $readonly ? 'disabled' : '' }}>
                            <label for="auftrag_id">Auftrags-ID</label>
                            @error('auftrag_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Auftrags-Datum --}}
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input
                                type="date"
                                class="form-control @error('auftrag_datum') is-invalid @enderror"
                                id="auftrag_datum"
                                name="auftrag_datum"
                                placeholder=" "
                                value="{{ old('auftrag_datum', $rechnung->auftrag_datum?->toDateString()) }}"
                                {{ $readonly ? 'disabled' : '' }}>
                            <label for="auftrag_datum">Auftrags-Datum</label>
                            @error('auftrag_datum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @verbatim
    <script>
        (function () {
            // Hilfsformatierer: Zahl → "22,00 %" (de-DE)
            function formatPercent(val) {
                var n = Number(val);
                if (!isFinite(n)) return '–';
                return n.toLocaleString('de-DE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' %';
            }

            // "Ja/Nein" aus Boolean
            function jaNein(b) { return b ? 'Ja' : 'Nein'; }

            // DOM-Elemente
            var select   = document.getElementById('fattura_profile_id');
            var infoLine = document.getElementById('fattura_profile_info');
            var dataEl   = document.getElementById('fattura_profiles_data');

            // Ohne Daten nicht fortfahren (robust in create/edit)
            if (!select || !infoLine || !dataEl) return;

            // JSON aus dem Script-Tag laden
            var profiles = [];
            try {
                profiles = JSON.parse(dataEl.textContent || '[]');
            } catch (e) {
                profiles = [];
            }

            // Index per ID für O(1)-Lookup
            var byId = {};
            profiles.forEach(function (p) { byId[String(p.id)] = p; });

            // Renderer für die Infozeile
            function renderInfo(profileId) {
                var p = byId[String(profileId)];
                if (!p) {
                    infoLine.innerHTML = 'Kein Profil ausgewählt.';
                    return;
                }

                // Text kompakt + eindeutig
                var parts = [];
                parts.push('<strong>' + (p.bezeichnung || 'Profil') + '</strong>');
                parts.push('MwSt: ' + formatPercent(p.mwst_satz));
                parts.push('Split Payment: ' + jaNein(!!p.split_payment));
                parts.push('Ritenuta: ' + jaNein(!!p.ritenuta));

                infoLine.innerHTML = parts.join(' &nbsp;•&nbsp; ');
            }

            // Initial befüllen (bei edit mit vorausgewähltem Profil)
            renderInfo(select.value);

            // Live-Update bei Änderung
            select.addEventListener('change', function (e) {
                renderInfo(e.target.value);
            });
        })();
    </script>
    @endverbatim

    {{-- CARD: Summen & Ritenuta + Bemerkungen --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-calculator"></i> Summen & Bemerkungen
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-center">

                    {{-- Summen (readonly, werden automatisch berechnet) --}}
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text"
                                   class="form-control"
                                   value="{{ number_format($rechnung->netto_summe ?? 0, 2, ',', '.') }}"
                                   disabled>
                            <label>Netto-Summe (€)</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text"
                                   class="form-control"
                                   value="{{ number_format($rechnung->mwst_betrag ?? 0, 2, ',', '.') }}"
                                   disabled>
                            <label>MwSt-Betrag (€)</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text"
                                   class="form-control"
                                   value="{{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }}"
                                   disabled>
                            <label>Brutto-Summe (€)</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text"
                                   class="form-control fw-bold"
                                   value="{{ number_format($rechnung->zahlbar_betrag ?? 0, 2, ',', '.') }}"
                                   disabled>
                            <label>Zahlbar (€)</label>
                        </div>
                    </div>

                    {{-- Hinweis zu Ritenuta, falls aktiv --}}
                    @if($rechnung->ritenuta && $rechnung->ritenuta_betrag > 0)
                        <div class="col-md-6">
                            <div class="alert alert-info py-2 mb-0">
                                <i class="bi bi-info-circle"></i>
                                <strong>Ritenuta:</strong>
                                {{ number_format($rechnung->ritenuta_betrag, 2, ',', '.') }} €
                                ({{ $rechnung->ritenuta_prozent }}% auf Netto)
                            </div>
                        </div>
                    @endif

                    {{-- Interne Bemerkung --}}
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea name="bemerkung"
                                      class="form-control @error('bemerkung') is-invalid @enderror"
                                      style="height: 80px"
                                      {{ $readonly ? 'disabled' : '' }}>{{ old('bemerkung', $rechnung->bemerkung) }}</textarea>
                            <label>Interne Bemerkung (nur intern)</label>
                            @error('bemerkung') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Bemerkung für Kunde --}}
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea name="bemerkung_kunde"
                                      class="form-control @error('bemerkung_kunde') is-invalid @enderror"
                                      style="height: 80px"
                                      {{ $readonly ? 'disabled' : '' }}>{{ old('bemerkung_kunde', $rechnung->bemerkung_kunde) }}</textarea>
                            <label>Bemerkung auf Rechnung (für Kunde sichtbar)</label>
                            @error('bemerkung_kunde') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- BUTTONS: Zurück zum Gebäude & Löschen --}}
    @if($rechnung->exists)
        <div class="col-12 d-flex justify-content-between align-items-center mt-3">
            <div>
                @if($rechnung->gebaeude_id && Route::has('gebaeude.edit'))
                    <a href="{{ route('gebaeude.edit', $rechnung->gebaeude_id) }}"
                       class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left-circle"></i> Zurück zum Gebäude
                    </a>
                @endif
            </div>
            <div>
                @if($rechnung->ist_editierbar)
                    <button type="button"
                            id="btn-delete-rechnung"
                            class="btn btn-outline-danger"
                            data-delete-url="{{ route('rechnung.destroy', $rechnung->id) }}"
                            data-redirect-url="{{ $rechnung->gebaeude_id && Route::has('gebaeude.edit')
                                ? route('gebaeude.edit', $rechnung->gebaeude_id)
                                : route('rechnung.index') }}">
                        <i class="bi bi-trash"></i> Rechnung löschen
                    </button>
                @endif
            </div>
        </div>
    @endif

</div>

<script>
    // JS für den Lösch-Button (ohne verschachtelte Formulare)
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('btn-delete-rechnung');
        if (!btn) return;

        btn.addEventListener('click', function () {
            if (!confirm('Rechnung wirklich löschen? Dieser Vorgang kann nicht rückgängig gemacht werden.')) {
                return;
            }

            var url          = btn.getAttribute('data-delete-url');
            var redirectUrl  = btn.getAttribute('data-redirect-url');

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/plain, */*',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: new URLSearchParams({ _method: 'DELETE' })
            })
            .then(function (resp) {
                if (resp.ok) {
                    window.location.href = redirectUrl;
                } else {
                    return resp.text().then(function (t) {
                        alert('Löschen fehlgeschlagen:\n' + t);
                    });
                }
            })
            .catch(function (err) {
                alert('Netzwerkfehler beim Löschen: ' + err);
            });
        });
    });
</script>
