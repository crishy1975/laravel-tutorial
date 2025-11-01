<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Adresse;

class AdresseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Beispiel: echter Eintrag
        Adresse::create([
            'name' => 'Resch GmbH Meisterbetrieb',
            'strasse' => 'Neustifterweg',
            'hausnummer' => '32',
            'plz' => '39100',
            'wohnort' => 'Bozen',
            'provinz' => 'BZ',
            'land' => 'Italien',
            'telefon' => '+39 0471 280708',
            'email' => 'info@reschgmbh.it',
            'pec' => 'resch@pec.it',
            'steuernummer' => 'RSSCHR75A01B123X',
            'mwst_nummer' => 'IT01234567890',
            'codice_univoco' => 'C1QQYZR',
            'bemerkung' => 'Zentrale Firmenadresse für alle Rechnungen',
        ]);

        // 2. Zufällige Testadressen
        Adresse::factory()->count(10)->create();
    }
}
