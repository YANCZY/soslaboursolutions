<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;

class SuperadminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superadminType = UserType::query()
            ->where('user_type_name', 'Superadmin')
            ->firstOrFail();

        $sosClient = Client::query()
            ->where('company_name', 'SOS Labour Solutions')
            ->firstOrFail();

        $user = User::query()->updateOrCreate(
            ['email' => 'peter@soslaboursolutions.com.au'],
            [
                'first_name' => 'Pete',
                'last_name' => 'Taylor',
                'user_type_id' => $superadminType->id,
                'client_id' => $sosClient->id,
                'password' => 'soslaboursolutions@123',
                'email_verified_at' => now(),
                'status' => 'active',
            ],
        );

        $userdev = User::query()->updateOrCreate(
            ['email' => 'georgecall123456789@gmail.com'],
            [
                'first_name' => 'Developer',
                'last_name' => 'Call',
                'user_type_id' => $superadminType->id,
                'client_id' => $sosClient->id,
                'password' => 'soslaboursolutions@123',
                'email_verified_at' => now(),
                'status' => 'active',
            ],
        );

        $user->clients()->syncWithoutDetaching([$sosClient->id]);
        $userdev->clients()->syncWithoutDetaching([$sosClient->id]);
    }
}
