<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
                'password' => Hash::make('soslaboursolutions@123'),
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        $user->clients()->syncWithoutDetaching([$sosClient->id]);
    }
}
