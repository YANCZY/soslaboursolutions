<?php

namespace Database\Seeders;

use App\Models\UserType;
use Illuminate\Database\Seeder;

class UserTypeSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Superadmin',
            'SOS Admin',
            'SOS Standard',
            'Client Admin',
            'Client Standard',
            'Contractor',
        ])->each(function (string $userTypeName) {
            UserType::query()->firstOrCreate([
                'user_type_name' => $userTypeName,
            ]);
        });
    }
}
