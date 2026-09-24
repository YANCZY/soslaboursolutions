<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreLocationEnrichmentRequest;
use App\Services\LocationEnrichment\LocationEnrichmentService;
use Illuminate\Http\JsonResponse;

class LocationEnrichmentController extends Controller
{
    public function __invoke(
        StoreLocationEnrichmentRequest $request,
        LocationEnrichmentService $service,
    ): JsonResponse {
        $payload = $service->handle($request->validated());

        return response()->json([
            'message' => 'Location enrichment request received.',
            'data' => $payload,
        ]);
    }
}
