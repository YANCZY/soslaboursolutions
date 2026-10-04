<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\PreventInactivePasswordReset;
use App\Support\PassportTokenInspector;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

use Inertia\Middleware\EncryptHistory;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->redirectUsersTo('/');

        $middleware->web(append: [
            HandleAppearance::class,
            EnsureUserIsActive::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            PreventInactivePasswordReset::class,
        ]);

        $middleware->alias([
            'user-type' => \App\Http\Middleware\EnsureUserHasAllowedType::class,
            'active-company' => \App\Http\Middleware\EnsureCompanyIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, \Throwable $exception): bool =>
                $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(
            function (ValidationException $exception, Request $request) {
                if (! $request->is('api/v1/location-enrichment')) {
                    return null;
                }

                return response()->json([
                    'message' => 'Invalid request. Check the errors below.',
                    'error' => 'validation_failed',
                    'errors' => $exception->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        );


        $exceptions->render(
            function (
                AuthenticationException $exception,
                Request $request,
            ) {
                if (! $request->is('api/v1/location-enrichment')) {
                    return null;
                }

                if (! PassportTokenInspector::hasExpiredClaim(
                    $request->bearerToken(),
                )) {
                    return null;
                }

                return response()
                    ->json([
                        'message' => 'Token expired.',
                        'error' => 'invalid_token',
                    ], Response::HTTP_UNAUTHORIZED)
                    ->header(
                        'WWW-Authenticate',
                        'Bearer error="invalid_token", error_description="The access token expired"',
                    );
            },
        );
    })->create();
