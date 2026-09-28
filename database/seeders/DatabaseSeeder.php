<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Creates/refreshes the admin login: admin@gmail.com / Greet@123
        $this->call(AdminUserSeeder::class);

        // The renewal automation (created disabled).
        $this->call(AutomationSeeder::class);
    }
}
