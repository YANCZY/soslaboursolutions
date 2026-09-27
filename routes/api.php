<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LocationEnrichmentController;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::post('/location-enrichment', LocationEnrichmentController::class)
            ->middleware(
                EnsureClientIsResourceOwner::using(
                    'location-enrichment:write',
                ),
            )
            ->name('location-enrichment.store');
    });
