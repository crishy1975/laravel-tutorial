<?php

namespace Database\Seeders;

use App\Models\Tour;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TourSeeder extends Seeder
{
    /**
     * Führt das Seeding aus.
     */
    public function run(): void
    {
        // 🔹 Handverlesene Touren – idempotent via firstOrCreate
        $preset = [
            [
                'name' => 'Bozen – Innenstadt',
                'beschreibung' => 'Innenstadt-Routen (viele Mehrfamilienhäuser); kurze Anfahrten.',
                'aktiv' => true,
            ],
            [
                'name' => 'Bozen – Industriezone',
                'beschreibung' => 'Gewerbeobjekte; ideal vormittags/werktags.',
                'aktiv' => true,
            ],
            [
                'name' => 'Unterland – Leifers/Branzoll',
                'beschreibung' => 'Gemischte Objekte; kurze Wege, gute Parkmöglichkeiten.',
                'aktiv' => true,
            ],
            [
                'name' => 'Überetsch – Eppan/Kaltern',
                'beschreibung' => 'Ländlicher Bereich; größere Distanzen; saisonale Spitzen.',
                'aktiv' => true,
            ],
            [
                'name' => 'Sarntal – Bergtour',
                'beschreibung' => 'Witterungsabhängig; längere Fahrzeit; gebündelte Termine.',
                'aktiv' => false,
            ],
        ];

        foreach ($preset as $row) {
            // Eindeutigkeit über den Namen – verhindert Duplikate bei erneutem Seeding
            Tour::firstOrCreate(
                ['name' => $row['name']],
                [
                    'beschreibung' => $row['beschreibung'] ?? null,
                    'aktiv'        => $row['aktiv'] ?? true,
                ]
            );
        }

        // 🔹 Zusätzlich zufällige Touren über Factory (nur wenn du möchtest)
        // Passe die Anzahl nach Bedarf an:
        Tour::factory()
            ->count(10)
            ->create();

        // Optional: Beispiele für bestimmte Zustände
        // Tour::factory()->count(3)->inactive()->create();
        // Tour::factory()->count(5)->withoutDescription()->create();
    }
}
