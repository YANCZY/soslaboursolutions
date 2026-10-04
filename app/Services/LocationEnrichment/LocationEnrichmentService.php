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
     *     radius_km: int|float|string
     * } $payload
     * @return array<string, mixed>
     */
    public function handle(array $payload): array
    {
        $radiusKm = (float) $payload['radius_km'];
        $radiusMeters = (int) round($radiusKm * 1000);

        $origin = $this->geoapify->geocodeCity($payload['city']);

        $locations = $this->geoapify->nearbyPopulatedPlaces(
            $origin['latitude'],
            $origin['longitude'],
            $radiusMeters,
            $origin['place_id'],
            $origin['name'],
        );

        $nearbyLocations = collect($locations)
            ->filter(fn (array $location): bool => in_array(
                $location['type'],
                ['populated_place.city', 'populated_place.suburb'],
                true,
            ))
            ->map(fn (array $location): array => [
                'place_id' => $location['place_id'],
                'name' => $location['name'],
                'type' => match ($location['type']) {
                    'populated_place.city' => 'City',
                    'populated_place.suburb' => 'Suburb',
                },
                'state' => $location['state'],
                'distance_meters' => $location['distance_meters'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
            ])
            ->values()
            ->all();

        return [
            'id' => $payload['id'],
            'requested_city' => $payload['city'],
            'radius_km' => $radiusKm,
            'nearby_locations' => $nearbyLocations,
        ];
    }
}
