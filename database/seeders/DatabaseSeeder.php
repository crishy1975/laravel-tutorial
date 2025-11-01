<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Eigene Seeder ausführen
        $this->call([
            AdresseSeeder::class,
            GebaeudeSeeder::class,
            TourSeeder::class,
        ]);
    }
}
