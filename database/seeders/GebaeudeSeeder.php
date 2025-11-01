<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gebaeude;

class GebaeudeSeeder extends Seeder
{
    public function run(): void
    {
        // Beispiel-Gebäude mit echten Daten
        Gebaeude::create([
            'codex' => 'GB0001',
            'postadresse_id' => 1,
            'rechnungsempfaenger_id' => 1,
            'gebaeude_name' => 'Zentrale Werkstatt',
            'strasse' => 'Neustifterweg',
            'hausnummer' => '32',
            'plz' => '39100',
            'wohnort' => 'Bozen',
            'land' => 'Italien',
            'bemerkung' => 'Hauptgebäude für alle Wartungen',
            'geplante_reinigungen' => 4,
            'gemachte_reinigungen' => 3,
            'faellig' => false,
            'rechnung_schreiben' => false,
            'm03' => true,
            'm09' => true,
        ]);

        // zusätzliche Testgebäude
        Gebaeude::factory()->count(10)->create();
    }
}
