<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $company = Client::query()
            ->where('company_name', 'SOS Labour Solutions')
            ->first();

        if (! $company) {
            throw new RuntimeException(
                'SOS Labour Solutions is missing. Run ClientSeeder first.'
            );
        }

        $sites = [
            [
                'site_name' => 'Melbourne Sample Site',
                'is_active' => true,
                'street' => '11 Nicholson Street',
                'city' => 'Carlton',
                'state' => 'VIC',
                'postcode' => '3053',
                'country' => 'Australia',
            ],
            [
                'site_name' => null,
                'is_active' => false,
                'street' => 'Cultural Precinct, Stanley Place',
                'city' => 'South Brisbane',
                'state' => 'QLD',
                'postcode' => '4101',
                'country' => 'Australia',
            ],
        ];

        DB::transaction(function () use ($company, $sites) {
            foreach ($sites as $site) {
                $siteName = trim((string) ($site['site_name'] ?? ''));

                Site::query()->firstOrCreate(
                    [
                        'client_id' => $company->id,
                        'street' => $site['street'],
                        'city' => $site['city'],
                        'state' => $site['state'],
                        'postcode' => $site['postcode'],
                        'country' => $site['country'],
                    ],
                    [
                        'site_name' => $siteName !== ''
                            ? $siteName
                            : $company->company_name,
                        'is_active' => $site['is_active'],
                    ],
                );
            }
        });
    }
}
