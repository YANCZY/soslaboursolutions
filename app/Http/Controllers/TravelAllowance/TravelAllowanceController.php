<?php

namespace App\Http\Controllers\TravelAllowance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\StoreTravelAllowanceRequest;
use App\Models\TravelAllowance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Notifications\TravelAllowanceSubmittedForApprovalNotification;
use App\Models\User;
use App\Notifications\TravelAllowanceApprovalStatusNotification;
class TravelAllowanceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $companies = $user
            ->clients()
            ->select('clients.id', 'clients.company_name')
            ->orderBy('company_name')
            ->get();

        $workDetails = $user
            ->companyWorkDetails()
            ->get(['client_id', 'travel_allowance', 'travel_allowance_currency']);

        $travelAllowances = TravelAllowance::query()
            ->with('client:id,company_name')
            ->where('user_id', $user->id)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (TravelAllowance $allowance) => [
                'id' => $allowance->id,
                'date' => $allowance->date,
                'name' => $allowance->name,
                'client_id' => $allowance->client_id,
                'company' => $allowance->client?->company_name ?? '-',
                'description' => $allowance->description,
                'rate' => (float) $allowance->rate,
                'quantity' => (int) $allowance->quantity,
                'amount' => (float) $allowance->amount,
                'approval_status' => $allowance->approval_status,
            ])
            ->values();

        return Inertia::render('admin/travel-allowance/TravelAllowance', [
            'travelAllowances' => $travelAllowances,
            'companies' => $companies,
            'workDetails' => $workDetails,
        ]);
    }
    public function store(StoreTravelAllowanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        TravelAllowance::query()->create([
            'user_id' => $request->user()->id,
            'client_id' => $validated['client_id'],
            'date' => $validated['date'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'rate' => $validated['rate'],
            'quantity' => $validated['quantity'],
            'amount' => $validated['amount'],
        ]);

        return to_route('travel-allowance.index');
    }

    public function submitForApproval(Request $request)
    {
        $validated = $request->validate([
            'travel_allowance_ids' => ['required', 'array', 'min:1'],
            'travel_allowance_ids.*' => ['integer', 'exists:travel_allowances,id'],
        ]);

        $records = TravelAllowance::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $validated['travel_allowance_ids'])
            ->get();

        if ($records->count() !== count($validated['travel_allowance_ids'])) {
            abort(403, 'One or more travel allowance records are invalid.');
        }

        foreach ($records as $record) {
            if ($record->approval_status === 'pending') {
                abort(422, 'One or more travel allowance records are already pending approval.');
            }

            if ($record->approval_status === 'approved') {
                abort(422, 'One or more travel allowance records have already been approved.');
            }

            if ($record->approval_status === 'rejected') {
                abort(422, 'One or more travel allowance records have already been rejected.');
            }
        }

        TravelAllowance::query()
            ->whereIn('id', $records->pluck('id'))
            ->update([
                'approval_status' => 'pending',
                'submitted_for_approval_at' => now(),
            ]);

        $submittedBy = trim(
            collect([
                $request->user()->first_name,
                $request->user()->last_name,
            ])->filter()->implode(' ')
        );

        $clientIds = $records
            ->pluck('client_id')
            ->filter()
            ->unique()
            ->values();

        $approvers = $this->approverUsers($clientIds);

        $notification = new TravelAllowanceSubmittedForApprovalNotification(
            submittedBy: $submittedBy,
            url: route('for-approvals.index', ['approval_type' => 'travel-allowance']),
        );

        foreach ($approvers as $approver) {
            $approver->notify($notification);
        }

        return response()->json([
            'message' => 'Travel allowance submitted for approval successfully.',
        ]);
    }

    public function approve(Request $request, TravelAllowance $travelAllowance): JsonResponse
    {
        abort_unless($this->canApproveClient($request, $travelAllowance->client_id), 403);

        if ($travelAllowance->approval_status !== 'pending') {
            abort(422, 'Only pending travel allowance records can be approved.');
        }

        $travelAllowance->update([
            'approval_status' => 'approved',
        ]);

        $travelAllowance->user?->notify(new TravelAllowanceApprovalStatusNotification(
            status: 'approved',
            date: $travelAllowance->date,
            url: route('travel-allowance.index'),
        ));

        return response()->json([
            'message' => 'Travel allowance approved successfully.',
        ]);
    }

    public function reject(Request $request, TravelAllowance $travelAllowance): JsonResponse
    {
        abort_unless($this->canApproveClient($request, $travelAllowance->client_id), 403);

        if ($travelAllowance->approval_status !== 'pending') {
            abort(422, 'Only pending travel allowance records can be rejected.');
        }

        $travelAllowance->update([
            'approval_status' => 'rejected',
        ]);

        $travelAllowance->user?->notify(new TravelAllowanceApprovalStatusNotification(
            status: 'rejected',
            date: $travelAllowance->date,
            url: route('travel-allowance.index'),
        ));

        return response()->json([
            'message' => 'Travel allowance rejected successfully.',
        ]);
    }

    public function update(StoreTravelAllowanceRequest $request, TravelAllowance $travelAllowance): RedirectResponse
    {
        abort_unless($travelAllowance->user_id === $request->user()->id, 403);

        $validated = $request->validated();

        $travelAllowance->update([
            'client_id' => $validated['client_id'],
            'date' => $validated['date'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'rate' => $validated['rate'],
            'quantity' => $validated['quantity'],
            'amount' => $validated['amount'],
        ]);

        return to_route('travel-allowance.index');
    }

    private function approverUsers($clientIds)
    {
        return User::query()
            ->with('userType:id,user_type_name')
            ->whereHas('userType', function ($query) {
                $query->where('user_type_name', 'Client Admin');
            })
            ->where(function ($query) use ($clientIds) {
                $query
                    ->whereIn('client_id', $clientIds)
                    ->orWhereHas('clients', function ($clientQuery) use ($clientIds) {
                        $clientQuery->whereIn('clients.id', $clientIds);
                    });
            })
            ->get();
    }

    private function canApproveClient(Request $request, ?int $clientId): bool
    {
        if ($request->user()->userType?->user_type_name === 'Superadmin') {
            return true;
        }

        if (! $clientId) {
            return false;
        }

        return $request->user()->client_id === $clientId
            || $request->user()->clients()->where('clients.id', $clientId)->exists();
    }

    public function destroy(Request $request, TravelAllowance $travelAllowance): RedirectResponse
    {
        abort_unless($travelAllowance->user_id === $request->user()->id, 403);

        $travelAllowance->delete();

        return to_route('travel-allowance.index');
    }
}
