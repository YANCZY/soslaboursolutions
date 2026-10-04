<?php

namespace App\Services\Geoapify;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Illuminate\Support\Str;

class GeoapifyClient
{
    private const PLACE_CATEGORIES = [
        'populated_place.city',
        'populated_place.suburb',
    ];

    /**
     * @return array{
     *     name: string,
     *     formatted: string|null,
     *     place_id: string|null,
     *     latitude: float,
     *     longitude: float
     * }
     */
    public function geocodeCity(
        string $city,
        ?string $state = null,
    ): array {
        $searchText = collect([$city, $state])
            ->filter()
            ->implode(', ');

        $response = $this->request()
            ->get('/v1/geocode/search', [
                'text' => $searchText,
                'type' => 'city',
                'filter' => sprintf(
                    'countrycode:%s',
                    strtolower(
                        (string) config(
                            'services.geoapify.country_code',
                            'au',
                        ),
                    ),
                ),
                'format' => 'json',
                'lang' => 'en',
                'limit' => 1,
            ])
            ->throw();

        $result = data_get($response->json(), 'results.0');

        if (
            ! is_array($result)
            || ! is_numeric($result['lat'] ?? null)
            || ! is_numeric($result['lon'] ?? null)
        ) {
            throw new RuntimeException(
                'The supplied city could not be located.',
            );
        }

        return [
            'name' => (string) ($result['city'] ?? $city),
            'formatted' => $result['formatted'] ?? null,
            'place_id' => $result['place_id'] ?? null,
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
        ];
    }

    /**
     * @return list<array{
     *     place_id: string,
     *     name: string,
     *     type: string|null,
     *     city: string|null,
     *     suburb: string|null,
     *     state: string|null,
     *     country_code: string|null,
     *     distance_meters: int|null,
     *     latitude: float|null,
     *     longitude: float|null
     * }>
     */
    public function nearbyPopulatedPlaces(
    float $latitude,
    float $longitude,
    int $radius,
    ?string $excludedPlaceId = null,
    ?string $excludedPlaceName = null,
    ): array {

        $response = $this->request()
            ->post('/v2/places', [
                'categories' => self::PLACE_CATEGORIES,
                'filter' => [
                    'type' => 'circle',
                    'lon' => $longitude,
                    'lat' => $latitude,
                    'radius' => $radius,
                ],
                'bias' => [
                    'type' => 'proximity',
                    'lon' => $longitude,
                    'lat' => $latitude,
                ],
                'limit' => (int) config(
                    'services.geoapify.result_limit',
                    100,
                ),
                'lang' => 'en',
            ])
            ->throw();

        return collect($response->json('features', []))
            ->map(function (array $feature): ?array {
                $properties = $feature['properties'] ?? [];
                $categories = $properties['categories'] ?? [];

                $name = $properties['name']
                    ?? $properties['suburb']
                    ?? $properties['city']
                    ?? $properties['town']
                    ?? $properties['village']
                    ?? null;

                $placeId = $properties['place_id'] ?? null;

                if (! is_string($name) || ! is_string($placeId)) {
                    return null;
                }

                $type = collect($categories)->first(
                    fn (mixed $category): bool => is_string($category)
                        && in_array(
                            $category,
                            self::PLACE_CATEGORIES,
                            true,
                        ),
                );

                return [
                    'place_id' => $placeId,
                    'name' => $name,
                    'type' => $type,
                    'city' => $properties['city'] ?? null,
                    'suburb' => $properties['suburb'] ?? null,
                    'state' => $properties['state'] ?? null,
                    'country_code' => $properties['country_code'] ?? null,
                    'distance_meters' => isset($properties['distance'])
                        ? (int) $properties['distance']
                        : null,
                    'latitude' => isset($properties['lat'])
                        ? (float) $properties['lat']
                        : null,
                    'longitude' => isset($properties['lon'])
                        ? (float) $properties['lon']
                        : null,
                ];
            })
            ->filter()
            ->reject(
                fn (array $place): bool =>
                    (
                        $excludedPlaceId !== null
                        && $place['place_id'] === $excludedPlaceId
                    )
                    || (
                        $excludedPlaceName !== null
                        && $place['distance_meters'] !== null
                        && $place['distance_meters'] <= 50
                        && Str::lower(trim($place['name']))
                            === Str::lower(trim($excludedPlaceName))
                    ),
            )
            ->unique('place_id')
            ->sortBy(
                fn (array $place): int => $place['distance_meters']
                    ?? PHP_INT_MAX,
            )
            ->values()
            ->all();
    }

    private function request(): PendingRequest
    {
        $apiKey = config('services.geoapify.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException(
                'The Geoapify API key is not configured.',
            );
        }

        return Http::baseUrl(
            (string) config(
                'services.geoapify.base_url',
                'https://api.geoapify.com',
            ),
        )
            ->acceptJson()
            ->withHeaders([
                'x-api-key' => $apiKey,
            ])
            ->connectTimeout(5)
            ->timeout(15)
            ->retry(2, 250);
    }
}
