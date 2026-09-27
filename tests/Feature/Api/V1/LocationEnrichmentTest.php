<?php

use App\Services\LocationEnrichment\LocationEnrichmentService;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use Illuminate\Support\Facades\Http;

function authenticateLocationEnrichmentClient(): void
{
    $client = app(ClientRepository::class)
        ->createClientCredentialsGrantClient(
            'Zoho CRM Location Enrichment Test',
        );

    Passport::actingAsClient(
        $client,
        ['location-enrichment:write'],
    );
}

function createRejectedExpiredJwt(): string
{
    $encode = static fn (array|string $value): string => rtrim(
        strtr(
            base64_encode(
                is_array($value)
                    ? json_encode($value, JSON_THROW_ON_ERROR)
                    : $value,
            ),
            '+/',
            '-_',
        ),
        '=',
    );

    return implode('.', [
        $encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]),
        $encode([
            'exp' => now()->subMinute()->timestamp,
        ]),
        $encode('invalid-signature'),
    ]);
}

test('it accepts an authenticated client credentials request', function () {
    authenticateLocationEnrichmentClient();

    $payload = [
        'id' => '572576000012345678',
        'city' => 'Brisbane',
        'suburb' => 'Fortitude Valley',
    ];

    $this->postJson('/api/v1/location-enrichment', $payload)
        ->assertOk()
        ->assertJson([
            'message' => 'Location enrichment request received.',
            'data' => $payload,
        ]);
});

test('it rejects a request without an access token', function () {
    $this->postJson('/api/v1/location-enrichment', [
        'id' => '572576000012345678',
        'city' => 'Brisbane',
        'suburb' => 'Fortitude Valley',
    ])->assertUnauthorized();
});

test('it returns the token expired response', function () {
    $expiredToken = createRejectedExpiredJwt();

    $this->withToken($expiredToken)
        ->postJson('/api/v1/location-enrichment', [
            'id' => '572576000012345678',
            'city' => 'Brisbane',
            'suburb' => 'Fortitude Valley',
        ])
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'Token expired.',
            'error' => 'invalid_token',
        ])
        ->assertHeader(
            'WWW-Authenticate',
            'Bearer error="invalid_token", error_description="The access token expired"',
        );
});

test('it does not call the service when the json is empty', function () {
    authenticateLocationEnrichmentClient();

    $this->mock(
        LocationEnrichmentService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('handle');
        },
    );

    $this->postJson('/api/v1/location-enrichment', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'id',
            'city',
            'suburb',
        ]);
});

test('it excludes the origin from populated places within twenty kilometres', function () {
    authenticateLocationEnrichmentClient();

    config()->set('services.geoapify.api_key', 'test-api-key');
    config()->set('services.geoapify.radius_meters', 20000);
    config()->set('services.geoapify.country_code', 'au');
    config()->set('services.geoapify.result_limit', 100);

    Http::fake([
        'api.geoapify.com/v1/geocode/search*' => Http::response([
            'results' => [
                [
                    'city' => 'Brisbane',
                    'formatted' => 'Brisbane, Queensland, Australia',
                    'place_id' => 'brisbane-place-id',
                    'lat' => -27.4698,
                    'lon' => 153.0251,
                ],
            ],
        ]),

        'api.geoapify.com/v2/places*' => Http::response([
            'features' => [
                [
                    'properties' => [
                        'place_id' => 'fortitude-valley-place-id',
                        'name' => 'Fortitude Valley',
                        'suburb' => 'Fortitude Valley',
                        'city' => 'Brisbane',
                        'state' => 'Queensland',
                        'country_code' => 'au',
                        'distance' => 1700,
                        'lat' => -27.4565,
                        'lon' => 153.0345,
                        'categories' => [
                            'populated_place',
                            'populated_place.suburb',
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->postJson(
        '/api/v1/location-enrichment',
        [
            'id' => '572576000012345678',
            'city' => 'Brisbane',
            'state' => 'Queensland',
            'suburb' => 'Fortitude Valley',
        ],
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', '572576000012345678')
        ->assertJsonPath('data.origin.name', 'Brisbane')
        ->assertJsonPath('data.radius_meters', 20000)
        ->assertJsonPath(
            'data.locations.0.name',
            'Fortitude Valley',
        )
        ->assertJsonPath(
            'data.locations.0.distance_meters',
            1700,
        );

    Http::assertSentCount(2);
});
