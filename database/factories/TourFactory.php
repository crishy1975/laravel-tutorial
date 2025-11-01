<?php
// Datei: database/factories/TourFactory.php

namespace Database\Factories;

use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory für das Tour-Modell.
 * Erzeugt realistische Testdaten für Name/Beschreibung/Aktiv-Status.
 * Hinweis: Der Tabellenname (tour vs. touren) ist fürs Factory-Erzeugen egal,
 * entscheidend ist, dass dein Model (App\Models\Tour) korrekt auf die Tabelle zeigt.
 */
class TourFactory extends Factory
{
    /** Das zugehörige Model */
    protected $model = Tour::class;

    /**
     * Standard-Definition eines Tour-Datensatzes.
     */
    public function definition(): array
    {
        // Beispiel-Namen wie "Bozen Innenstadt Nord" o. "Unterland Woche 2"
        $ort      = $this->faker->randomElement(['Bozen', 'Unterland', 'Überetsch', 'Sarntal', 'Etschtal', 'Überetsch/Kaltern', 'Leifers']);
        $suffix   = $this->faker->randomElement(['Innenstadt', 'Industriezone', 'Nord', 'Süd', 'Woche 1', 'Woche 2', 'Woche 3']);
        $tourName = trim($ort.' '.$suffix);

        return [
            'name'         => $tourName,
            'beschreibung' => $this->faker->optional(0.7)->sentence(12), // ~70% haben Beschreibung
            'aktiv'        => $this->faker->boolean(85),                 // ~85% aktiv
            // created_at / updated_at / deleted_at handled by Eloquent
        ];
    }

    /**
     * Zustand: explizit inaktiv.
     */
    public function inactive(): self
    {
        return $this->state(fn () => ['aktiv' => false]);
    }

    /**
     * Zustand: ohne Beschreibung.
     */
    public function withoutDescription(): self
    {
        return $this->state(fn () => ['beschreibung' => null]);
    }

    /**
     * Zustand: garantierte eindeutige Namen (falls du viele Seeds laufen lässt).
     */
    public function uniqueName(): self
    {
        return $this->state(function () {
            $ort    = $this->faker->unique()->city;      // unique city
            $suffix = $this->faker->randomElement(['W1','W2','W3','Innenstadt','Industrie','Nord','Süd']);
            return ['name' => $ort.' '.$suffix];
        });
    }
}
