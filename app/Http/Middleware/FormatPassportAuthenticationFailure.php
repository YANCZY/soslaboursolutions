<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JsonException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\AuthenticationException;

class FormatPassportAuthenticationFailure
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (AuthenticationException $exception) {
            if ($this->hasExpiredClaim($request->bearerToken())) {
               return response()
                ->json([
                    'message' => 'Token expired.',
                    'error' => 'invalid_token',
                ], Response::HTTP_UNAUTHORIZED)
                ->header(
                    'WWW-Authenticate',
                    'Bearer error="invalid_token", error_description="The access token expired"',
                );
            }

            throw $exception;
        }
    }

    private function hasExpiredClaim(?string $token): bool
    {
        if (! is_string($token) || $token === '') {
            return false;
        }

        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            return false;
        }

        $payload = strtr($segments[1], '-_', '+/');
        $paddingLength = (4 - strlen($payload) % 4) % 4;
        $payload .= str_repeat('=', $paddingLength);

        $decodedPayload = base64_decode($payload, true);

        if ($decodedPayload === false) {
            return false;
        }

        try {
            $claims = json_decode(
                $decodedPayload,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            return false;
        }

        return is_array($claims)
            && isset($claims['exp'])
            && is_numeric($claims['exp'])
            && (int) $claims['exp'] <= now()->timestamp;
    }
}
