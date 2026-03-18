<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExtractMessungController extends Controller
{
    /**
     * Prompt für Wöhler Abgasmessgerät-Display
     */
    private function getDisplayPrompt(): string
    {
        return 'Analysiere dieses Foto eines Wöhler Abgasmessgeräts. Extrahiere die Messwerte und gib sie als JSON zurück.

Auf dem Display findest du oben rechts Datum und Uhrzeit im Format:
- Zeile 1: Uhrzeit (z.B. "10:58:41")
- Zeile 2: Datum (z.B. "15.01.26")
- Zeile 3: "WLAN pronto" oder ähnlich (IGNORIEREN!)

Verwende GENAU diese Feldnamen:
{
  "typ": "display",
  "datum": "TT.MM.JJJJ",
  "uhrzeit": "HH:MM",
  "brennstoff": "FUEL_NAT_GAS oder FUEL_LIGHT_OIL oder FUEL_PROPANE oder FUEL_PELLETS oder FUEL_WOOD",
  "o2": "Sauerstoff in %",
  "co2": "CO2 in %",
  "qa": "Abgasverlust Qs in %",
  "co": "CO normiert (COn) in mg/m³",
  "nox": "NOx normiert (NOxn) in mg/m³",
  "t_luft": "Lufttemperatur TA in °C",
  "t_abgas": "Abgastemperatur TF in °C",
  "russ": "Rußzahl (falls vorhanden, sonst 0)"
}

Wichtig:
- Uhrzeit ist IMMER im Format HH:MM (z.B. "10:58"), NICHT "WLAN pronto"!
- Datum 2-stelliges Jahr zu 4-stellig: 26 → 2026
- Verwende COn (normiert), nicht COv
- Verwende NOxn (normiert), nicht NOxv  
- Nur Zahlen ohne Einheiten
- Bei "Gas naturale" oder "Erdgas" → FUEL_NAT_GAS
- Bei "Gasolio" oder "Heizöl" → FUEL_LIGHT_OIL
- Bei "GPL" oder "Flüssiggas" → FUEL_PROPANE
- Bei "Pellet" oder "Pellets" → FUEL_PELLETS
- Bei "Holz" oder "Legna" → FUEL_WOOD

Antworte NUR mit dem JSON, kein anderer Text.';
    }

    /**
     * Prompt für handgeschriebenes Messprotokoll (Bescheinigung/Attestazione)
     */
    private function getProtokollPrompt(): string
    {
        return 'Analysiere dieses Foto eines handgeschriebenen Messprotokolls (Bescheinigung / Attestazione). Extrahiere den Anlagen-CODE und die Messergebnisse als JSON.

Das Formular ist zweisprachig (Deutsch/Italienisch).

SUCHE NACH:

1. CODE: Steht rechts neben "TECHNISCHE DATEN DER ANLAGE / DATI TECNICI DELL\'IMPIANTO". Ist eine Nummer (z.B. "5210").

2. BRENNSTOFF/COMBUSTIBILE: Eine Zeile mit Kästchen: Heizöl/Gasolio, Erdgas/Metano, Flüssiggas/GPL, Holz/Legna, Pellets/Pellet, Sonstiges/Altro. Eines ist angekreuzt.

3. MESSERGEBNISSE / RISULTATI DELLA MISURA:
- Tag der Messung / Data della misura (TT.MM.JJ)
- Messergebnis entspricht Verordnung? Ja oder Nein angekreuzt
- Rußzahl-Mittelwert / Opacità-valore medio (links)
- Ölderivate? / Sostanze oleose? Ja oder Nein angekreuzt (rechts)
- Wärmeträgertemperatur / Temperatura termoconvettore (links, °C)
- Abgastemperatur / Temperatura gas di combustione (rechts, °C)
- Verbrennungslufttemperatur / Temperatura aria di combustione (links, °C)
- Sauerstoffgehalt / Ossigeno (rechts, %)
- Kohlendioxidgehalt / Anidride carbonica (links, %)
- Stickoxid / Ossidi di azoto (rechts, mg/m³)
- Kohlenmonoxid / Monossido di carbonio (links, mg/m³)

Verwende GENAU diese Feldnamen:
{
  "typ": "protokoll",
  "kodex": "Anlagen-CODE",
  "brennstoff": "FUEL_NAT_GAS oder FUEL_LIGHT_OIL oder FUEL_PROPANE oder FUEL_PELLETS oder FUEL_WOOD",
  "datum": "TT.MM.JJJJ",
  "ergebnis": "1 wenn Ja, 0 wenn Nein",
  "russ": "Rußzahl-Mittelwert",
  "oelderivate": "0 wenn Nein/No, 1 wenn Ja/Si",
  "t_waerme": "Wärmeträgertemperatur",
  "t_abgas": "Abgastemperatur",
  "t_luft": "Verbrennungslufttemperatur",
  "o2": "Sauerstoffgehalt",
  "co2": "Kohlendioxidgehalt",
  "nox": "Stickoxid",
  "co": "Kohlenmonoxid"
}

Wichtig:
- Die Werte sind HANDGESCHRIEBEN, lies sie sorgfältig!
- Datum 2-stelliges Jahr zu 4-stellig: 25 → 2025, 26 → 2026
- Nur Zahlen ohne Einheiten
- Dezimaltrennzeichen als Punkt (nicht Komma): 8,3 → "8.3", 6,2 → "6.2"
- Brennstoff-Mapping: Erdgas/Metano → FUEL_NAT_GAS, Heizöl/Gasolio → FUEL_LIGHT_OIL, Flüssiggas/GPL → FUEL_PROPANE, Pellets → FUEL_PELLETS, Holz/Legna → FUEL_WOOD
- Ölderivate: Schaue ob Ja/Si oder Nein/No angekreuzt/durchgestrichen ist
- Wenn CODE leer ist, gib leeren String zurück

Antworte NUR mit dem JSON, kein anderer Text.';
    }

    public function extract(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
            'source' => 'sometimes|string|in:display,protokoll,auto',
        ]);

        $apiKey = config('services.anthropic.api_key');
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API-Key nicht konfiguriert'
            ]);
        }

        try {
            // Media-Type aus Base64-Daten erkennen
            $mediaType = 'image/jpeg';
            if (str_starts_with($request->image, '/9j/')) {
                $mediaType = 'image/jpeg';
            } elseif (str_starts_with($request->image, 'iVBOR')) {
                $mediaType = 'image/png';
            } elseif (str_starts_with($request->image, 'R0lG')) {
                $mediaType = 'image/gif';
            } elseif (str_starts_with($request->image, 'UklG')) {
                $mediaType = 'image/webp';
            }

            $source = $request->input('source', 'auto');

            // Bei "auto" erst erkennen was es ist
            if ($source === 'auto') {
                $source = $this->detectSource($request->image, $mediaType, $apiKey);
            }

            // Prompt basierend auf Quelle wählen
            $prompt = $source === 'protokoll' 
                ? $this->getProtokollPrompt() 
                : $this->getDisplayPrompt();

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 800,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mediaType,
                                    'data' => $request->image,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $content = $response->json('content.0.text');
                
                // JSON aus der Antwort extrahieren
                $content = trim($content);
                if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                    $content = $matches[0];
                }
                
                $data = json_decode($content, true);
                
                if ($data) {
                    // Jahr korrigieren wenn nur 2-stellig
                    if (isset($data['datum'])) {
                        $data['datum'] = preg_replace('/\.(\d{2})$/', '.20$1', $data['datum']);
                    }
                    
                    return response()->json(array_merge(['success' => true], $data));
                }
                
                return response()->json([
                    'success' => false,
                    'error' => 'Konnte Werte nicht extrahieren'
                ]);
            }

            Log::error('Anthropic API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'API-Fehler: ' . $response->status()
            ]);

        } catch (\Exception $e) {
            Log::error('Extract Messung Error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Erkennt automatisch ob es ein Display-Foto oder ein Protokoll ist
     */
    private function detectSource(string $imageData, string $mediaType, string $apiKey): string
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 20,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mediaType,
                                    'data' => $imageData,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => 'Ist dieses Bild ein digitales Messgerät-Display oder ein Papier-Formular/Protokoll? Antworte NUR mit einem Wort: "display" oder "protokoll"',
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $answer = strtolower(trim($response->json('content.0.text')));
                if (str_contains($answer, 'protokoll') || str_contains($answer, 'papier') || str_contains($answer, 'formular')) {
                    return 'protokoll';
                }
            }
        } catch (\Exception $e) {
            Log::warning('Auto-detect source failed, defaulting to display', ['error' => $e->getMessage()]);
        }

        return 'display';
    }
}
