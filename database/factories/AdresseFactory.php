<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AdresseFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'strasse' => $this->faker->streetName(),
            'hausnummer' => $this->faker->buildingNumber(),
            'plz' => $this->faker->postcode(),
            'wohnort' => $this->faker->city(),
            'provinz' => strtoupper($this->faker->lexify('??')), // z. B. BZ, TN
            'land' => $this->faker->country(),
            'telefon' => $this->faker->phoneNumber(),
            'handy' => $this->faker->e164PhoneNumber(),
            'email' => $this->faker->companyEmail(),
            'email_zweit' => $this->faker->safeEmail(),
            'pec' => $this->faker->unique()->userName() . '@pec.it',
            'steuernummer' => strtoupper($this->faker->bothify('RSSCHR##A##B###X')),
            'mwst_nummer' => strtoupper($this->faker->bothify('IT###########')),
            'codice_univoco' => strtoupper($this->faker->bothify('???????')),
            'bemerkung' => $this->faker->sentence(),
            'veraendert' => false,
            'veraendert_wann' => null,
        ];
    }
}
