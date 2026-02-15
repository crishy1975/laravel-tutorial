<div class="p-6">
    <h2 class="text-xl font-semibold mb-4">Anlagen importieren (CSV)</h2>

    {{-- Upload-Formular --}}
    <div class="mb-6">
        <form wire:submit="import">
            <div class="flex items-end gap-4">
                <div class="flex-1">
                    <label for="csvFile" class="block text-sm font-medium text-gray-700 mb-1">
                        CSV-Datei auswählen
                    </label>
                    <input 
                        type="file" 
                        id="csvFile"
                        wire:model="csvFile"
                        accept=".csv,.txt"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    >
                    @error('csvFile') 
                        <span class="text-red-500 text-sm">{{ $message }}</span> 
                    @enderror
                </div>

                <button 
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="import"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="import">Importieren</span>
                    <span wire:loading wire:target="import">Importiere...</span>
                </button>

                @if($importResult || count($errors) > 0)
                    <button 
                        type="button"
                        wire:click="resetImport"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                    >
                        Zurücksetzen
                    </button>
                @endif
            </div>
        </form>

        {{-- Loading während Datei-Upload --}}
        <div wire:loading wire:target="csvFile" class="mt-2 text-sm text-gray-500">
            Datei wird hochgeladen...
        </div>
    </div>

    {{-- Ergebnis --}}
    @if($importResult)
        <div class="bg-green-50 border border-green-200 rounded p-4 mb-4">
            <h3 class="font-semibold text-green-800 mb-2">Import abgeschlossen</h3>
            <ul class="text-sm text-green-700">
                <li>Gesamt verarbeitet: <strong>{{ $importResult['total'] }}</strong></li>
                <li>Neu importiert: <strong>{{ $importResult['imported'] }}</strong></li>
                <li>Übersprungen (existiert bereits): <strong>{{ $importResult['skipped'] }}</strong></li>
                @if($importResult['errors'] > 0)
                    <li class="text-red-600">Fehler: <strong>{{ $importResult['errors'] }}</strong></li>
                @endif
            </ul>
        </div>
    @endif

    {{-- Fehler --}}
    @if(count($errors) > 0)
        <div class="bg-red-50 border border-red-200 rounded p-4 mb-4">
            <h3 class="font-semibold text-red-800 mb-2">Fehler beim Import</h3>
            <ul class="text-sm text-red-700 max-h-40 overflow-y-auto">
                @foreach($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Info-Box --}}
    <div class="bg-gray-50 border border-gray-200 rounded p-4">
        <h3 class="font-semibold text-gray-700 mb-2">Hinweise</h3>
        <ul class="text-sm text-gray-600 list-disc list-inside">
            <li>CSV-Datei mit Semikolon (;) als Trennzeichen</li>
            <li>Nur neue Anlagen werden importiert (Kodex noch nicht vorhanden)</li>
            <li>Bestehende Anlagen werden übersprungen, nicht aktualisiert</li>
        </ul>
    </div>
</div>
