<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Anlagen</h2>
        <a href="{{ route('messungen.anlagen.import') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            CSV Import
        </a>
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded p-4 text-center">
            <div class="text-2xl font-bold text-blue-700">{{ $statistik['total'] }}</div>
            <div class="text-sm text-blue-600">Anlagen gesamt</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded p-4 text-center">
            <div class="text-2xl font-bold text-green-700">{{ $statistik['mitMessung'] }}</div>
            <div class="text-sm text-green-600">Mit Messung {{ $filterJahr }}</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded p-4 text-center">
            <div class="text-2xl font-bold text-red-700">{{ $statistik['ohneMessung'] }}</div>
            <div class="text-sm text-red-600">Ohne Messung {{ $filterJahr }}</div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-gray-50 border rounded p-4 mb-4">
        <div class="grid grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Kodex</label>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="filterKodex"
                    class="w-full px-2 py-1 border rounded text-sm"
                    placeholder="Kodex..."
                >
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Beschreibung</label>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="filterBeschreibung"
                    class="w-full px-2 py-1 border rounded text-sm"
                    placeholder="Beschreibung..."
                >
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Ort</label>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="filterOrt"
                    class="w-full px-2 py-1 border rounded text-sm"
                    placeholder="Ort..."
                >
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Straße</label>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="filterStrasse"
                    class="w-full px-2 py-1 border rounded text-sm"
                    placeholder="Straße..."
                >
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Messung {{ $filterJahr }}</label>
                <select 
                    wire:model.live="filterGemessen"
                    class="w-full px-2 py-1 border rounded text-sm"
                >
                    <option value="">Alle</option>
                    <option value="1">Ja</option>
                    <option value="0">Nein</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Jahr</label>
                <div class="flex gap-1">
                    <input 
                        type="number" 
                        wire:model.live="filterJahr"
                        class="w-full px-2 py-1 border rounded text-sm"
                        min="2020"
                        max="2030"
                    >
                    <button 
                        wire:click="resetFilters"
                        class="px-2 py-1 bg-gray-200 rounded text-sm hover:bg-gray-300"
                        title="Filter zurücksetzen"
                    >
                        ✕
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabelle --}}
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-2 py-2 text-left text-sm cursor-pointer hover:bg-gray-200" wire:click="sortBy('Feld_a')">
                        Kodex
                        @if($sortField === 'Feld_a')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="border px-2 py-2 text-left text-sm cursor-pointer hover:bg-gray-200" wire:click="sortBy('Feld_w')">
                        Beschreibung
                        @if($sortField === 'Feld_w')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="border px-2 py-2 text-left text-sm cursor-pointer hover:bg-gray-200" wire:click="sortBy('Feld_i')">
                        Ort
                        @if($sortField === 'Feld_i')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="border px-2 py-2 text-left text-sm cursor-pointer hover:bg-gray-200" wire:click="sortBy('Feld_k')">
                        Straße
                        @if($sortField === 'Feld_k')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="border px-2 py-2 text-center text-sm w-20">Messung</th>
                    <th class="border px-2 py-2 text-left text-sm cursor-pointer hover:bg-gray-200" wire:click="sortBy('Feld_y')">
                        Hersteller
                        @if($sortField === 'Feld_y')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="border px-2 py-2 text-center text-sm w-16">Baujahr</th>
                    <th class="border px-2 py-2 text-center text-sm w-16">kW</th>
                    <th class="border px-2 py-2 text-center text-sm w-24">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($anlagen as $anlage)
                    @php
                        $hatMessung = $anlage->messungenHeuer()->exists();
                    @endphp
                    <tr class="hover:bg-gray-50 {{ !$hatMessung ? 'bg-red-50' : '' }}">
                        <td class="border px-2 py-1 text-sm font-mono">{{ $anlage->Feld_a }}</td>
                        <td class="border px-2 py-1 text-sm">{{ Str::limit($anlage->Feld_w, 40) }}</td>
                        <td class="border px-2 py-1 text-sm">{{ $anlage->Feld_i }}</td>
                        <td class="border px-2 py-1 text-sm">{{ $anlage->Feld_k }} {{ $anlage->Feld_n }}</td>
                        <td class="border px-2 py-1 text-center">
                            @if($hatMessung)
                                <span class="text-green-600">✓</span>
                            @else
                                <span class="text-red-600">✗</span>
                            @endif
                        </td>
                        <td class="border px-2 py-1 text-sm">{{ $anlage->Feld_y }}</td>
                        <td class="border px-2 py-1 text-sm text-center">{{ $anlage->Feld_z }}</td>
                        <td class="border px-2 py-1 text-sm text-center">{{ $anlage->Feld_ab }}</td>
                        <td class="border px-2 py-1 text-center">
                            <a 
                                href="{{ route('messungen.anlagen.edit', $anlage->Feld_a) }}"
                                class="inline-block px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200"
                                title="Bearbeiten"
                            >
                                ✎
                            </a>
                            <a 
                                href="{{ route('messungen.neu', $anlage->Feld_a) }}"
                                class="inline-block px-2 py-1 bg-green-100 text-green-700 rounded text-xs hover:bg-green-200"
                                title="Neue Messung"
                            >
                                +
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="border px-4 py-8 text-center text-gray-500">
                            Keine Anlagen gefunden.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $anlagen->links() }}
    </div>
</div>
