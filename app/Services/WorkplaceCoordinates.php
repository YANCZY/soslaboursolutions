<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Site;
use RuntimeException;

class WorkplaceCoordinates
{
    public function resolve(Client|Site $workplace): array
    {
        $current = $workplace->fresh();

        if (! $current) {
            throw new RuntimeException(
                'The selected workplace is unavailable.'
            );
        }

        $latitude = $current->geofence_latitude;
        $longitude = $current->geofence_longitude;

        if (
            $current->geofence_pinned_at === null
            || ! is_numeric($latitude)
            || ! is_numeric($longitude)
            || ! is_finite((float) $latitude)
            || ! is_finite((float) $longitude)
            || (float) $latitude < -90
            || (float) $latitude > 90
            || (float) $longitude < -180
            || (float) $longitude > 180
        ) {
            throw new RuntimeException(
                'An administrator must pin this workplace location before attendance can be recorded.'
            );
        }

        return [
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ];
    }
}
