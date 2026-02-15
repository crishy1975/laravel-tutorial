<div class="p-6 max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">Anlage bearbeiten: {{ $Feld_a }}</h2>
        <a href="{{ route('messungen.anlagen.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
            ← Zurück
        </a>
    </div>

    @if($saved)
        <div class="bg-green-50 border border-green-200 rounded p-4 mb-4">
            <span class="text-green-700">✓ Änderungen gespeichert.</span>
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        
        {{-- Identifikation --}}
        <div class="bg-gray-50 border rounded p-4">
            <h3 class="font-semibold mb-3 text-gray-700">Identifikation</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Kodex (Feld_a)</label>
                    <input type="text" wire:model="Feld_a" class="w-full px-3 py-2 border rounded bg-gray-100" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Gemeindecode</label>
                    <input type="text" wire:model="Feld_b" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Nummer</label>
                    <input type="text" wire:model="Feld_c" class="w-full px-3 py-2 border rounded">
                </div>
            </div>
        </div>

        {{-- Standort --}}
        <div class="bg-gray-50 border rounded p-4">
            <h3 class="font-semibold mb-3 text-gray-700">Standort</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Ort (IT)</label>
                    <input type="text" wire:model="Feld_h" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Ort (DE)</label>
                    <input type="text" wire:model="Feld_i" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Straße (IT)</label>
                    <input type="text" wire:model="Feld_j" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Straße (DE)</label>
                    <input type="text" wire:model="Feld_k" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Hausnummer</label>
                    <input type="text" wire:model="Feld_n" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Fraktion</label>
                    <input type="text" wire:model="Feld_t" class="w-full px-3 py-2 border rounded">
                </div>
            </div>
        </div>

        {{-- Kontakt/Verwalter --}}
        <div class="bg-gray-50 border rounded p-4">
            <h3 class="font-semibold mb-3 text-gray-700">Kontakt / Verwalter</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Kontaktperson</label>
                    <input type="text" wire:model="Feld_o" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Verwalter</label>
                    <input type="text" wire:model="Feld_l" class="w-full px-3 py-2 border rounded">
                </div>
            </div>
        </div>

        {{-- Anlage --}}
        <div class="bg-gray-50 border rounded p-4">
            <h3 class="font-semibold mb-3 text-gray-700">Heizanlage</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Beschreibung</label>
                    <input type="text" wire:model="Feld_w" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Hersteller</label>
                    <input type="text" wire:model="Feld_y" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Baujahr</label>
                    <input type="text" wire:model="Feld_z" class="w-full px-3 py-2 border rounded" maxlength="4">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Leistung (kW)</label>
                    <input type="text" wire:model="Feld_ab" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Anzahl Kessel / Status</label>
                    <input type="text" wire:model="Feld_x" class="w-full px-3 py-2 border rounded">
                </div>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="flex gap-3">
            <button 
                type="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
                Speichern
            </button>
            <a 
                href="{{ route('messungen.anlagen.index') }}"
                class="px-6 py-2 bg-gray-300 rounded hover:bg-gray-400"
            >
                Abbrechen
            </a>
        </div>
    </form>

    {{-- Messungen dieser Anlage --}}
    <div class="mt-8 bg-gray-50 border rounded p-4">
        <h3 class="font-semibold mb-3 text-gray-700">Messungen dieser Anlage</h3>
        @php
            $messungen = $anlage->messungen()->orderByDesc('cMIS_DATA')->take(10)->get();
        @endphp
        @if($messungen->isEmpty())
            <p class="text-gray-500">Keine Messungen vorhanden.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-2 py-1 text-left">Datum</th>
                        <th class="px-2 py-1 text-left">Brennstoff</th>
                        <th class="px-2 py-1 text-center">Ergebnis</th>
                        <th class="px-2 py-1 text-center">CO</th>
                        <th class="px-2 py-1 text-center">NOx</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($messungen as $messung)
                        <tr>
                            <td class="px-2 py-1">{{ $messung->cMIS_DATA2 }}</td>
                            <td class="px-2 py-1">{{ $messung->cMIS_COMBUSTIBILE_P }}</td>
                            <td class="px-2 py-1 text-center">
                                @if($messung->strEsito === '1')
                                    <span class="text-green-600">positiv</span>
                                @else
                                    <span class="text-red-600">negativ</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 text-center">{{ $messung->cMIS_MONOSSSIDO }}</td>
                            <td class="px-2 py-1 text-center">{{ $messung->cMIS_BIOSSIDO_AZOTO }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
