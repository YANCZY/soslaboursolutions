<?php

namespace App\Services;

use App\Models\UserLocationAssignment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\ValidationException;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\Site;
use RuntimeException;

class AttendanceGeofence
{
    public function __construct(
            private readonly WorkplaceCoordinates $workplaceCoordinates,
        ) {}

        public function assertWithin(
        UserLocationAssignment $assignment,
        array $position,
    ): void {
        $target = $assignment->site_id !== null
            ? $assignment->site
            : $assignment->company;

        if (! $target) {
            $this->reject('The selected workplace is unavailable.');
        }

        if (
            $target instanceof Site
            && (int) $target->client_id !== (int) $assignment->client_id
        ) {
            $this->reject('The selected workplace is invalid.');
        }

        $this->assertAtWorkplace($target, $position);
    }

    public function assertForAttendance(
        Attendance $attendance,
        array $position,
    ): void {
        $company = Client::query()->find($attendance->client_id);

        if (! $company) {
            $this->reject('The attendance workplace is unavailable.');
        }

        if ($attendance->site_id !== null) {
            $target = Site::query()
                ->whereKey($attendance->site_id)
                ->where('client_id', $company->id)
                ->first();

            if (! $target) {
                $this->reject('The attendance site is unavailable.');
            }
        } else {
            $target = $company;
        }

        $this->assertAtWorkplace($target, $position);
    }

    private function assertAtWorkplace(
        Client|Site $target,
        array $position,
    ): void {
        $locationKey = $target instanceof Site
            ? 'site:' . $target->getKey()
            : 'company:' . $target->getKey();


        $radii = config('attendance.location_radii', []);

        $radius = (float) (
            $target->geofence_radius_meters
            ?? $radii[$locationKey]
            ?? config('attendance.check_in_radius_meters', 100)
        );

        if (! is_finite($radius) || $radius <= 0) {
            $this->reject(
                'The workplace check-in radius is not configured correctly.'
            );
        }

        try {
            $workplace = $this->workplaceCoordinates->resolve($target);
        } catch (ConnectionException | RequestException | RuntimeException $exception) {
            \Illuminate\Support\Facades\Log::warning(
                'Attendance workplace verification failed.',
                [
                    'location_key' => $locationKey,
                    'exception_type' => get_class($exception),
                    'http_status' => $exception instanceof RequestException
                        ? $exception->response->status()
                        : null,
                ],
            );

            $this->reject(
                'The workplace location could not be verified. '
                . 'Please try again or contact management.'
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

        // Compare the reported position directly with the saved workplace pin.
        if ($distance > $radius) {
            $this->reject(
                'You must be within ' . $radius
                . ' metres of the workplace for this attendance action. '
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
