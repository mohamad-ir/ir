<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Base structure every installation needs. Sample content lives in DemoContentSeeder. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            TaxonomySeeder::class,
            SynonymSeeder::class,
            MenuSeeder::class,
            PageSeeder::class,
        ]);
    }
}
