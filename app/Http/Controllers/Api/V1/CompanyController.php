<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCompanyRequest;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = Client::query()->create($request->validated());

        // Load database defaults, including is_active.
        $company->refresh();

        return (new CompanyResource($company))
            ->additional([
                'message' => 'Company created successfully.',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
