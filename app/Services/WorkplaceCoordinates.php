<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Site;
use App\Services\Geoapify\GeoapifyClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WorkplaceCoordinates
{
    public function __construct(
        private readonly GeoapifyClient $geoapify,
    ) {}

    public function resolve(
        Client|Site $workplace,
        bool $allowGeocoding = false,
    ): array {

        $current = $workplace->fresh();

        if (! $current) {
            throw new RuntimeException(
                'The selected workplace is unavailable.'
            );
        }

        [, $addressHash] = $this->addressDetails($current);

        $cacheKey = 'workplace-coordinates:v1:'
            . $current->getTable()
            . ':'
            . $current->getKey()
            . ':'
            . $addressHash;

        // Valid stored coordinates do not require a geocoding lock.
        if (
            $current->geofence_latitude !== null
            && $current->geofence_longitude !== null
            && $current->geofence_address_hash === $addressHash
        ) {
            return Cache::remember(
                $cacheKey,
                now()->addDay(),
                fn (): array => [
                    'latitude' => (float) $current->geofence_latitude,
                    'longitude' => (float) $current->geofence_longitude,
                ],
            );
        }

        // Attendance transactions must only use prepared coordinates.
        if (! $allowGeocoding) {
            throw new RuntimeException(
                'The workplace location is not ready or its address changed. '
                . 'Please try again.'
            );
        }

        $lockKey = 'workplace-geocode:'
            . $workplace->getTable()
            . ':'
            . $workplace->getKey();

        // Prevent simultaneous requests from geocoding the same workplace.
        $result = Cache::lock($lockKey, 90)->get(
            function () use ($workplace): array {
                $current = $workplace->fresh();

                if (! $current) {
                    throw new RuntimeException(
                        'The selected workplace is unavailable.'
                    );
                }

                [$address, $addressHash] = $this->addressDetails($current);

                $cacheKey = 'workplace-coordinates:v1:'
                    . $current->getTable()
                    . ':'
                    . $current->getKey()
                    . ':'
                    . $addressHash;

                // Reuse database coordinates when the address still matches.
                if (
                    $current->geofence_latitude !== null
                    && $current->geofence_longitude !== null
                    && $current->geofence_address_hash === $addressHash
                ) {
                    return Cache::remember(
                        $cacheKey,
                        now()->addDay(),
                        fn (): array => [
                            'latitude' => (float) $current->geofence_latitude,
                            'longitude' => (float) $current->geofence_longitude,
                        ],
                    );
                }

                // Only call Geoapify for missing or outdated coordinates.
                $coordinates = $this->geoapify->geocodeAddress($address);

                // Save only if the address did not change during the lookup.
                DB::transaction(function () use (
                    $current,
                    $addressHash,
                    $coordinates,
                ): void {
                    $latest = $current->newQuery()
                        ->whereKey($current->getKey())
                        ->lockForUpdate()
                        ->first();

                    if (! $latest) {
                        throw new RuntimeException(
                            'The selected workplace is unavailable.'
                        );
                    }

                    [, $latestHash] = $this->addressDetails($latest);

                    if ($latestHash !== $addressHash) {
                        throw new RuntimeException(
                            'The workplace address changed. Please try again.'
                        );
                    }

                    $latest->forceFill([
                        'geofence_latitude' => $coordinates['latitude'],
                        'geofence_longitude' => $coordinates['longitude'],
                        'geofence_address_hash' => $addressHash,
                        'geofence_geocoded_at' => now(),
                    ])->save();
                });

                Cache::put(
                    $cacheKey,
                    $coordinates,
                    now()->addDay(),
                );

                return $coordinates;
            },
        );

        if ($result === false) {
            throw new RuntimeException(
                'The workplace location is being updated. Please try again.'
            );
        }

        return $result;
    }

    private function addressDetails(Client|Site $workplace): array
    {
        $isSite = $workplace instanceof Site;

        $street = $isSite
            ? $workplace->street
            : $workplace->company_address;

        if (trim((string) $street) === '') {
            throw new RuntimeException(
                'This workplace has no address. Contact management.'
            );
        }

        $parts = $isSite
            ? [
                $workplace->street,
                $workplace->city,
                $workplace->state,
                $workplace->postcode,
                $workplace->country,
            ]
            : [
                $workplace->company_address,
                $workplace->company_address_2,
                $workplace->company_address_city,
                $workplace->company_address_state,
                $workplace->company_address_zip,
                $workplace->company_address_country,
            ];

        $normalisedParts = array_map(
            static fn ($part): string => mb_strtolower(
                preg_replace('/\s+/u', ' ', trim((string) $part))
            ),
            $parts,
        );

        $address = implode(', ', array_filter(
            $normalisedParts,
            static fn (string $part): bool => $part !== '',
        ));

        $addressHash = hash('sha256', json_encode([
            'parts' => $normalisedParts,
            'country_code' => strtolower(
                (string) config('services.geoapify.country_code', 'au')
            ),
        ], JSON_THROW_ON_ERROR));

        return [$address, $addressHash];
    }
}
