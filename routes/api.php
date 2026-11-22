<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LocationEnrichmentController;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;
use App\Http\Controllers\Api\V1\MobileAuthController;
use App\Http\Middleware\Mobile\EnsureMobileAccess;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\UserController;

Route::get('/user', [MobileAuthController::class, 'me'])
    ->middleware(['auth:api', EnsureMobileAccess::class]);

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::prefix('mobile/auth')
        ->name('mobile.auth.')
        ->group(function (): void {
            Route::post('login', [MobileAuthController::class, 'login'])
                ->middleware('throttle:mobile-login')
                ->name('login');

            Route::post('two-factor', [MobileAuthController::class, 'twoFactor'])
                ->middleware('throttle:mobile-two-factor')
                ->name('two-factor');

            Route::middleware([
                'auth:api',
                EnsureMobileAccess::class,
                'throttle:60,1',
            ])->group(function (): void {
                Route::get('me', [MobileAuthController::class, 'me'])
                    ->name('me');

                Route::post('logout', [MobileAuthController::class, 'logout'])
                    ->name('logout');
            });
        });

        Route::post('/location-enrichment', LocationEnrichmentController::class)->middleware(EnsureClientIsResourceOwner::using('location-enrichment:write',),)->name('location-enrichment.store');
        Route::post('companies', [CompanyController::class, 'store'])
            ->middleware([EnsureClientIsResourceOwner::using('companies:create'),'throttle:60,1',])->name('companies.store');
        Route::post('sites', [SiteController::class, 'store'])
            ->middleware([EnsureClientIsResourceOwner::using('sites:create'),'throttle:60,1',])->name('sites.store');
        Route::post('users', [UserController::class, 'store'])
            ->middleware([EnsureClientIsResourceOwner::using('users:create'),'throttle:60,1',])->name('users.store');

});
