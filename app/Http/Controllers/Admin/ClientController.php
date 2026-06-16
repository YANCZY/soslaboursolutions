<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreClientRequest;
use App\Jobs\StoreClient;
use App\Models\Client;
use App\Models\ClientSaveRequest;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('admin/clients/index', [
            'clients' => Client::query()
                ->latest()
                ->get([
                    'id',
                    'company_name',
                    'phone',
                    'industry',
                    'website',
                    'company_address',
                ]),
            'view' => $request->query('view', 'list'),
            'saveRequestId' => session('client_save_request_id'),
        ]);
    }

    public function store(StoreClientRequest $request)
    {
        $validated = $request->validated();

        $nextAction = $validated['next_action'];
        unset($validated['next_action']);

        $saveRequest = ClientSaveRequest::create([
            'user_id' => $request->user()->id,
            'payload' => $validated,
            'status' => 'queued',
        ]);

        StoreClient::dispatch($saveRequest->id);

        return redirect()
            ->route('clients.index', $nextAction === 'add' ? ['view' => 'add'] : [])
            ->with('client_save_request_id', $saveRequest->id);
    }

    public function saveStatus(ClientSaveRequest $clientSaveRequest)
    {
        abort_unless($clientSaveRequest->user_id === Auth::id(),403);

        return response()->json([
            'status' => $clientSaveRequest->status,
        ]);
    }
}
