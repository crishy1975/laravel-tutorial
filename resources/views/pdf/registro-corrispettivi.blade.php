{{--
════════════════════════════════════════════════════════════════════════════
DATEI: registro-corrispettivi.blade.php
PFAD:  resources/views/pdf/registro-corrispettivi.blade.php
════════════════════════════════════════════════════════════════════════════
--}}
@php
    // ── Firmendaten (hier anpassen) ─────────────────────────────────
    $firma = [
        'name'       => 'Resch GmbH',
        'untertitel' => 'Kaminkehrermeister Maestro Spazzacamino',
        'adresse'    => 'Resch GmbH, Galvanistr. 6, 39100 Bozen (BZ)',
        'piva'       => 'MwSt.-Nr./P.IVA 01699660211',
    ];

    // Logo: public/images/logo.png (wird eingebettet, fehlt es → kein Logo)
    $logoPfad = public_path('images/logo.png');
    $logo = file_exists($logoPfad)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPfad))
        : null;

    $eur = fn($v) => number_format((float) $v, 2, ',', '.') . ' €';
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Registro dei corrispettivi</title>
<style>
    @page { margin: 14mm 15mm 16mm 15mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #000; }

    .kopf { width: 100%; border-collapse: collapse; border-bottom: 1px solid #000; }
    .kopf td { vertical-align: middle; padding-bottom: 4mm; }
    .kopf .logo { width: 32mm; }
    .kopf .logo img { max-width: 30mm; max-height: 26mm; }
    .kopf .firma { font-size: 26pt; line-height: 1.1; }
    .kopf .unter { font-size: 12.5pt; margin-top: 3mm; }
    .absender { font-size: 7.5pt; margin: 2mm 0 0 8mm; }

    h1 { font-size: 21pt; font-weight: normal; margin: 14mm 0 6mm 0; }

    .meta { border-collapse: collapse; margin-bottom: 5mm; }
    .meta td { padding: 1mm 8mm 1mm 0; font-size: 8.5pt; }

    table.reg { width: 100%; border-collapse: separate; border-spacing: 1.2mm; margin-left: -1.2mm; }
    table.reg th, table.reg td { border: 1px solid #000; padding: 1.4mm 1.6mm; font-size: 8.5pt; }
    table.reg th { background: #c0c0c0; font-weight: normal; text-align: left; vertical-align: top; }
    table.reg td.r { text-align: right; white-space: nowrap; }
    table.reg td.nr { width: 7mm; }
    table.reg tr.summe td { background: #c0c0c0; vertical-align: top; }

    .klein { font-size: 7.5pt; color: #333; }
    .scorporo { margin-top: 6mm; }
    .scorporo th, .scorporo td { font-size: 8pt !important; }

    .fuss { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 7pt; color: #555; text-align: right; }
    .umbruch { page-break-after: always; }
</style>
</head>
<body>

<div class="fuss">{{ $firma['name'] }} – {{ $firma['piva'] }} – Registro dei corrispettivi</div>

@foreach($seiten as $s)
    @include('pdf.partials.registro-kopf')

    <h1>Registro dei corrispettivi</h1>

    <table class="meta">
        <tr>
            <td>Jahr/Anno:</td><td style="padding-right: 22mm;">{{ $s['jahr'] }}</td>
            <td>Monat/Mese:</td><td>{{ $s['label_de'] }} / {{ $s['label_it'] }}</td>
        </tr>
        <tr>
            <td>Seite/Pagina:</td><td>{{ $s['pagina'] }}</td>
            <td></td><td></td>
        </tr>
    </table>

    <table class="reg">
        <thead>
            <tr>
                <th>R</th>
                <th>Datum/Data</th>
                <th>Corrispettivi 10%</th>
                <th>Corrispettivi 22%</th>
                <th>Totale Corrispettivi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($s['eintraege'] as $i => $e)
                <tr>
                    <td class="nr">{{ $i + 1 }}</td>
                    <td>{{ $e->datum->format('d.m.Y') }}</td>
                    <td class="r">{{ $eur($e->betrag_10) }}</td>
                    <td class="r">{{ $eur($e->betrag_22) }}</td>
                    <td class="r">{{ $eur($e->gesamt) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Nessun corrispettivo nel mese / Keine Inkassi in diesem Monat</td></tr>
            @endforelse
            <tr class="summe">
                <td colspan="2">Totale del mese:<br>Gesamtsumme Monat:</td>
                <td class="r">{{ $eur($s['summe10']) }}</td>
                <td class="r">{{ $eur($s['summe22']) }}</td>
                <td class="r">{{ $eur($s['summe']) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="reg scorporo" style="width: 70%;">
        <thead>
            <tr>
                <th>Scorporo IVA / MwSt.-Ausweis</th>
                <th>Imponibile / Netto</th>
                <th>IVA / MwSt.</th>
                <th>Totale / Brutto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Aliquota 10%</td>
                <td class="r">{{ $eur($s['scorp10']['imponibile']) }}</td>
                <td class="r">{{ $eur($s['scorp10']['iva']) }}</td>
                <td class="r">{{ $eur($s['summe10']) }}</td>
            </tr>
            <tr>
                <td>Aliquota 22%</td>
                <td class="r">{{ $eur($s['scorp22']['imponibile']) }}</td>
                <td class="r">{{ $eur($s['scorp22']['iva']) }}</td>
                <td class="r">{{ $eur($s['summe22']) }}</td>
            </tr>
        </tbody>
    </table>

    @if(!$loop->last || $uebersicht)
        <div class="umbruch"></div>
    @endif
@endforeach

{{-- ── Jahresübersicht (nur beim Jahres-PDF) ─────────────────────── --}}
@if($uebersicht)
    @include('pdf.partials.registro-kopf')

    <h1>Riepilogo annuale {{ $uebersicht['jahr'] }}</h1>
    <p class="klein" style="margin-top: -3mm;">Jahresübersicht der Tagesinkassi</p>

    <table class="reg">
        <thead>
            <tr>
                <th>Mese / Monat</th>
                <th>Giorni / Tage</th>
                <th>Corrispettivi 10%</th>
                <th>Corrispettivi 22%</th>
                <th>Totale Corrispettivi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($uebersicht['monate'] as $m)
                <tr>
                    <td>{{ $m['label_it'] }}</td>
                    <td class="r">{{ $m['eintraege']->count() }}</td>
                    <td class="r">{{ $eur($m['summe10']) }}</td>
                    <td class="r">{{ $eur($m['summe22']) }}</td>
                    <td class="r">{{ $eur($m['summe']) }}</td>
                </tr>
            @endforeach
            <tr class="summe">
                <td>Totale anno:<br>Gesamtsumme Jahr:</td>
                <td class="r">{{ collect($uebersicht['monate'])->sum(fn($m) => $m['eintraege']->count()) }}</td>
                <td class="r">{{ $eur($uebersicht['summe10']) }}</td>
                <td class="r">{{ $eur($uebersicht['summe22']) }}</td>
                <td class="r">{{ $eur($uebersicht['summe']) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="reg scorporo" style="width: 70%;">
        <thead>
            <tr>
                <th>Scorporo IVA / MwSt.-Ausweis</th>
                <th>Imponibile / Netto</th>
                <th>IVA / MwSt.</th>
                <th>Totale / Brutto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Aliquota 10%</td>
                <td class="r">{{ $eur($uebersicht['scorp10']['imponibile']) }}</td>
                <td class="r">{{ $eur($uebersicht['scorp10']['iva']) }}</td>
                <td class="r">{{ $eur($uebersicht['summe10']) }}</td>
            </tr>
            <tr>
                <td>Aliquota 22%</td>
                <td class="r">{{ $eur($uebersicht['scorp22']['imponibile']) }}</td>
                <td class="r">{{ $eur($uebersicht['scorp22']['iva']) }}</td>
                <td class="r">{{ $eur($uebersicht['summe22']) }}</td>
            </tr>
        </tbody>
    </table>
@endif

</body>
</html>
