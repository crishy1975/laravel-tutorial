<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExtractMessungController extends Controller
{
    public function extract(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
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

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 500,
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
                                'text' => 'Analysiere dieses Foto eines Wöhler Abgasmessgeräts. Extrahiere die Messwerte und gib sie als JSON zurück. Verwende GENAU diese Feldnamen:

{
  "datum": "TT.MM.JJJJ",
  "uhrzeit": "HH:MM",
  "brennstoff": "FUEL_NAT_GAS oder FUEL_LIGHT_OIL oder FUEL_PROPANE",
  "o2": "Sauerstoff in %",
  "co2": "CO2 in %",
  "qa": "Abgasverlust in %",
  "co": "CO normiert (COn) in mg/m³",
  "nox": "NOx normiert (NOxn) in mg/m³",
  "t_luft": "Lufttemperatur TA in °C",
  "t_abgas": "Abgastemperatur TF in °C",
  "t_waerme": "Wärmeträgertemperatur Trg in °C",
  "russ": "Rußzahl (falls vorhanden)"
}

Wichtig:
- Verwende COn (normiert), nicht COv
- Verwende NOxn (normiert), nicht NOxv
- Datum im Format TT.MM.JJJJ (z.B. 15.01.2026)
- Nur Zahlen, keine Einheiten
- Bei "Gas naturale" oder "Erdgas" → FUEL_NAT_GAS
- Bei "Gasolio" oder "Heizöl" → FUEL_LIGHT_OIL
- Bei "GPL" oder "Flüssiggas" → FUEL_PROPANE

Antworte NUR mit dem JSON, kein anderer Text.'
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
}
