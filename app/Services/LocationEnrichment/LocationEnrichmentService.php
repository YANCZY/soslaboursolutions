<?php

namespace App\Services\LocationEnrichment;

use App\Services\Geoapify\GeoapifyClient;

class LocationEnrichmentService
{
    public function __construct(
        private readonly GeoapifyClient $geoapify,
    ) {
    }

    /**
     * @param array{
     *     id: string,
     *     city: string,
     *     state?: string|null,
     *     suburb: string
     * } $payload
     * @return array<string, mixed>
     */
    public function handle(array $payload): array
    {
        $origin = $this->geoapify->geocodeCity(
            $payload['city'],
            $payload['state'] ?? null,
        );

        $locations = $this->geoapify->nearbyPopulatedPlaces(
            $origin['latitude'],
            $origin['longitude'],
            $origin['place_id'],
            $origin['name'],
        );

        return [
            'id' => $payload['id'],
            'requested_city' => $payload['city'],
            'requested_suburb' => $payload['suburb'],
            'radius_meters' => (int) config(
                'services.geoapify.radius_meters',
                20000,
            ),
            'locations' => $locations,
        ];
    }
}
