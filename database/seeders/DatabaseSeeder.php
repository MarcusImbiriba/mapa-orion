<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PoliceUnitSeeder::class,
        ]);

        // Application users are created interactively with the users:create command.
    }
}
