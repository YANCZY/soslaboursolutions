<?php

return [
    // Default workplace boundary.
    'check_in_radius_meters' => 100,

    // Reject location readings with greater uncertainty.
    'max_accuracy_meters' => 50,

    // Optional overrides for specific workplaces.
    'location_radii' => [
        // 'company:1' => 150,
        // 'site:2' => 300,
    ],

    'position_max_age_seconds' => 60,
];
