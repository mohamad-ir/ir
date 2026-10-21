<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        Permissions::sync();
    }
}
