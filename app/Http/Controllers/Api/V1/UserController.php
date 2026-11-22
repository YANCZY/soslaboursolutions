<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Services\ZohoUserProvisioner;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function store(
        StoreUserRequest $request,
        ZohoUserProvisioner $provisioner,
    ): JsonResponse {
        $result = $provisioner->create($request->validated());

        return response()->json([
            'message' => $result['created']
                ? 'User created and invitation queued.'
                : 'This Zoho record was already provisioned.',
            'replayed' => ! $result['created'],
            'data' => $result['data'],
        ], $result['created']
            ? Response::HTTP_CREATED
            : Response::HTTP_OK);
    }
}
