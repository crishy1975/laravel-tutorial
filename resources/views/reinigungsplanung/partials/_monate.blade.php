{{-- resources/views/reinigungsplanung/partials/_monate.blade.php --}}
{{-- Kompakte Anzeige der Reinigungsmonate eines Gebäudes                    --}}
{{--  - keine Monate  → "–"                                                   --}}
{{--  - 1–3 Monate    → Kürzel-Chips (Mär  Okt), aktueller Monat hervorgehoben --}}
{{--  - ab 4 Monaten  → Mini-Jahresleiste mit 12 Strichen                     --}}
{{-- Klick öffnet das Bearbeiten-Modal des Gebäudes.                          --}}

@php
    $kurz  = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    $aktiv = $g->monate_aktiv ?? [];
    $jetzt = (int) now()->month;
    $titel = empty($aktiv)
        ? 'Keine Monate geplant'
        : collect($aktiv)->map(fn ($m) => $monate[$m] ?? $kurz[$m - 1])->implode(', ');
@endphp

<button type="button" class="monate-anzeige" data-id="{{ $g->id }}"
        title="{{ $titel }} – klicken zum Bearbeiten">
    @if(empty($aktiv))
        <span class="monate-leer">–</span>
    @elseif(count($aktiv) <= 3)
        @foreach($aktiv as $m)
            <span class="monat-chip {{ $m === $jetzt ? 'jetzt' : '' }}">{{ $kurz[$m - 1] }}</span>
        @endforeach
    @else
        <span class="monat-strip">
            @for($m = 1; $m <= 12; $m++)
                <i class="{{ in_array($m, $aktiv, true) ? 'on' : '' }} {{ $m === $jetzt ? 'jetzt' : '' }}"></i>
            @endfor
        </span>
    @endif
</button>
