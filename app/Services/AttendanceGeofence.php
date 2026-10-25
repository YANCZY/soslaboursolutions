<?php

namespace App\Services;

use App\Models\UserLocationAssignment;
use App\Services\Geoapify\GeoapifyClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AttendanceGeofence
{
    public function __construct(
        private readonly GeoapifyClient $geoapify,
    ) {}

    public function assertWithin(
        UserLocationAssignment $assignment,
        array $position,
    ): void {
        $isSite = $assignment->site_id !== null;

        $target = $isSite
            ? $assignment->site
            : $assignment->company;

        if (! $target) {
            $this->reject('The selected workplace is unavailable.');
        }

        $street = $isSite
            ? $target->street
            : $target->company_address;

        if (trim((string) $street) === '') {
            $this->reject(
                'This workplace has no address. Contact management.'
            );
        }

        $parts = $isSite
            ? [
                $target->street,
                $target->city,
                $target->state,
                $target->postcode,
                $target->country,
            ]
            : [
                $target->company_address,
                $target->company_address_2,
                $target->company_address_city,
                $target->company_address_state,
                $target->company_address_zip,
                $target->company_address_country,
            ];

        $address = collect($parts)
            ->map(fn ($part) => trim((string) $part))
            ->filter(fn ($part) => $part !== '')
            ->implode(', ');

        $locationKey = $isSite
            ? 'site:' . $assignment->site_id
            : 'company:' . $assignment->client_id;

        $radii = config('attendance.location_radii', []);

        $radius = (float) (
            $radii[$locationKey]
            ?? config('attendance.check_in_radius_meters', 10)
        );

        if (! is_finite($radius) || $radius <= 0) {
            $this->reject(
                'The workplace check-in radius is not configured correctly.'
            );
        }

        $accuracy = (float) $position['accuracy'];

        if ($accuracy > $radius) {
            $this->reject(
                'Your location is not accurate enough. Enable precise '
                . 'location, move to an open area, and try again.'
            );
        }

        try {
            // Explicitly use file caching, not the database cache store.
            // Address changes produce a different cache key.
            $cacheKey = 'attendance-geocode-v1:' . hash(
                'sha256',
                $address . ':' . config('services.geoapify.country_code', 'au')
            );

            $workplace = Cache::store('file')->remember(
                $cacheKey,
                now()->addDay(),
                fn () => $this->geoapify->geocodeAddress($address),
            );
        } catch (ConnectionException | RequestException | RuntimeException $exception) {
                \Illuminate\Support\Facades\Log::warning(
                    'Attendance workplace verification failed.',
                    [
                        'location_key' => $locationKey,
                        'exception_type' => get_class($exception),
                        'http_status' => $exception instanceof RequestException
                            ? $exception->response->status()
                            : null,
                        'reason' => $exception instanceof RuntimeException
                            ? $exception->getMessage()
                            : null,
                    ],
                );
        }

        $ageMilliseconds = microtime(true) * 1000
            - (float) $position['captured_at'];

        if (
            $ageMilliseconds < -10000
            || $ageMilliseconds
                > config('attendance.position_max_age_seconds', 60) * 1000
        ) {
            $this->reject(
                'Your location reading has expired or your device clock '
                . 'is incorrect. Check your device time and try again.'
            );
        }

        $distance = $this->distanceMeters(
            (float) $position['latitude'],
            (float) $position['longitude'],
            (float) $workplace['latitude'],
            (float) $workplace['longitude'],
        );

        // Require the reported position and its accuracy margin
        // to fit inside the boundary.
        if ($distance + $accuracy > $radius) {
            $this->reject(
                'You must be within ' . $radius
                . ' metres of the selected workplace to check in. '
                . 'Move closer and try again.'
            );
        }
    }

    private function distanceMeters(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB,
    ): float {
        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitudeA))
            * cos(deg2rad($latitudeB))
            * sin($longitudeDelta / 2) ** 2;

        $a = max(0.0, min(1.0, $a));

        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'geolocation' => $message,
        ]);
    }
}
