<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSiteRequest;
use App\Http\Resources\Api\V1\SiteResource;
use App\Models\Client;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    public function store(StoreSiteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $site = DB::transaction(function () use ($validated): Site {
            $company = Client::query()
                ->whereKey($validated['client_id'])
                ->lockForUpdate()
                ->first();

            if (! $company || ! $company->is_active) {
                throw ValidationException::withMessages([
                    'client_id' =>
                        'The selected company does not exist or is inactive.',
                ]);
            }

            unset($validated['client_id']);

            $site = $company->sites()->create($validated);

            return $site->refresh();
        });

        return (new SiteResource($site))
            ->additional([
                'message' => 'Site created successfully.',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
