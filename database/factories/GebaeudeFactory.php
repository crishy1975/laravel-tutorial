<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Adresse;

class GebaeudeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codex' => strtoupper($this->faker->bothify('GB####')),
            'postadresse_id' => Adresse::factory(),
            'rechnungsempfaenger_id' => Adresse::factory(),
            'gebaeude_name' => $this->faker->company() . ' Gebäude',
            'strasse' => $this->faker->streetName(),
            'hausnummer' => $this->faker->buildingNumber(),
            'plz' => $this->faker->postcode(),
            'wohnort' => $this->faker->city(),
            'land' => $this->faker->country(),
            'bemerkung' => $this->faker->sentence(),
            'letzter_termin' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'datum_faelligkeit' => $this->faker->dateTimeBetween('now', '+1 year'),
            'geplante_reinigungen' => $this->faker->numberBetween(1, 5),
            'gemachte_reinigungen' => $this->faker->numberBetween(0, 5),
            'faellig' => $this->faker->boolean(20),
            'rechnung_schreiben' => $this->faker->boolean(10),
            'm01' => $this->faker->boolean(10),
            'm02' => $this->faker->boolean(10),
            'm03' => $this->faker->boolean(10),
            'm04' => $this->faker->boolean(10),
            'm05' => $this->faker->boolean(10),
            'm06' => $this->faker->boolean(10),
            'm07' => $this->faker->boolean(10),
            'm08' => $this->faker->boolean(10),
            'm09' => $this->faker->boolean(10),
            'm10' => $this->faker->boolean(10),
            'm11' => $this->faker->boolean(10),
            'm12' => $this->faker->boolean(10),
            'select_tour' => null,
        ];
    }
}
