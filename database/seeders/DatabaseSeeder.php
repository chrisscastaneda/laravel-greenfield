<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Sample users for exploring the admin area locally. Real users are
        // created when they first sign in through WorkOS.
        User::factory(10)->create();
        User::factory()->deactivated()->create();
    }
}
