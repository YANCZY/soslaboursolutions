<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserType;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $adminUserType = UserType::query()->firstOrCreate([
            'user_type_name' => 'Admin',
        ]);

        User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'user_type_id' => $adminUserType->id,
            'email' => 'test@example.com',
        ]);
    }
}
