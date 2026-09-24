<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyLocationEnrichmentHmac
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isJson()) {
            return new JsonResponse([
                'message' => 'The Content-Type must be application/json.',
            ], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        $secret = config('services.location_enrichment.hmac_secret');

        if (! is_string($secret) || $secret === '') {
            return new JsonResponse([
                'message' => 'API authentication is unavailable.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $timestamp = $request->header('X-SOS-Timestamp');
        $providedSignature = $request->header('X-SOS-Signature');

        if (
            ! is_string($timestamp)
            || ! ctype_digit($timestamp)
            || ! is_string($providedSignature)
            || preg_match('/^[a-f0-9]{64}$/i', $providedSignature) !== 1
        ) {
            return $this->unauthorized();
        }

        $tolerance = (int) config(
            'services.location_enrichment.hmac_tolerance_seconds',
            300,
        );

        if (abs(now()->timestamp - (int) $timestamp) > $tolerance) {
            return $this->unauthorized();
        }

        $signedContent = $timestamp.'.'.$request->getContent();

        $expectedSignature = hash_hmac(
            'sha256',
            $signedContent,
            $secret,
        );

        if (! hash_equals($expectedSignature, strtolower($providedSignature))) {
            return $this->unauthorized();
        }

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Unauthorized request.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
