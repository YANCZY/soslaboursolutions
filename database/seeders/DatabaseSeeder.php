<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserType;
use App\Models\Client;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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

    //    User::factory()
    //     ->count(10)
    //     ->sequence(fn ($sequence) => [
    //         'email' => 'user' . ($sequence->index + 1) . '@sos.com',
    //     ])
    //     ->create([
    //         'first_name' => Str::random(10),
    //         'last_name' => Str::random(10),
    //         'user_type_id' => $adminUserType->id,
    //         'password' => bcrypt('password123'),
    //     ]);

       $clients = collect([
            'Northstar Manufacturing Inc.',
            'BrightPath Logistics Corp.',
            'Harborline Foods Ltd.',
            'SummitCare Services',
            'MetroBuild Contractors',
        ])->map(fn (string $companyName) => Client::firstOrCreate([
            'company_name' => $companyName,
        ]));

        User::query()
            ->whereNull('client_id')
            ->get()
            ->each(function (User $user) use ($clients): void {
                $user->update([
                    'client_id' => $clients->random()->id,
                ]);
            });


    }
}
