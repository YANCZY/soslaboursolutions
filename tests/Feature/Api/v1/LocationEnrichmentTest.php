<?php

use App\Services\LocationEnrichment\LocationEnrichmentService;
use Mockery\MockInterface;

beforeEach(function () {
    config()->set(
        'services.location_enrichment.hmac_secret',
        'test-hmac-secret',
    );

    config()->set(
        'services.location_enrichment.hmac_tolerance_seconds',
        300,
    );
});

/**
 * @param array<string, mixed> $payload
 * @return array<string, string>
 */
function locationEnrichmentHmacHeaders(
    array $payload,
    ?int $timestamp = null,
): array {
    $timestamp ??= now()->timestamp;
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    return [
        'X-SOS-Timestamp' => (string) $timestamp,
        'X-SOS-Signature' => hash_hmac(
            'sha256',
            $timestamp.'.'.$body,
            config('services.location_enrichment.hmac_secret'),
        ),
    ];
}

test('it accepts a correctly signed location enrichment request', function () {
    $payload = [
        'id' => '572576000012345678',
        'city' => 'Brisbane',
        'suburb' => 'Fortitude Valley',
    ];

    $response = $this->postJson(
        '/api/v1/location-enrichment',
        $payload,
        locationEnrichmentHmacHeaders($payload),
    );

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Location enrichment request received.',
            'data' => $payload,
        ]);
});

test('it rejects a request without an hmac signature', function () {
    $response = $this->postJson('/api/v1/location-enrichment', [
        'id' => '572576000012345678',
        'city' => 'Brisbane',
        'suburb' => 'Fortitude Valley',
    ]);

    $response
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'Unauthorized request.',
        ]);
});

test('it rejects an invalid hmac signature', function () {
    $payload = [
        'id' => '572576000012345678',
        'city' => 'Brisbane',
        'suburb' => 'Fortitude Valley',
    ];

    $headers = locationEnrichmentHmacHeaders($payload);
    $headers['X-SOS-Signature'] = str_repeat('0', 64);

    $this->postJson(
        '/api/v1/location-enrichment',
        $payload,
        $headers,
    )->assertUnauthorized();
});

test('it rejects an expired hmac timestamp', function () {
    $payload = [
        'id' => '572576000012345678',
        'city' => 'Brisbane',
        'suburb' => 'Fortitude Valley',
    ];

    $expiredTimestamp = now()->subMinutes(6)->timestamp;

    $this->postJson(
        '/api/v1/location-enrichment',
        $payload,
        locationEnrichmentHmacHeaders($payload, $expiredTimestamp),
    )->assertUnauthorized();
});

test('it does not call the service when the json is empty', function () {
    $this->mock(
        LocationEnrichmentService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('handle');
        },
    );

    $payload = [];

    $response = $this->postJson(
        '/api/v1/location-enrichment',
        $payload,
        locationEnrichmentHmacHeaders($payload),
    );

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'id',
            'city',
            'suburb',
        ]);
});
