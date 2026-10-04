<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\EmailSending\SendAccountAccessLink;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\UserType;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;



class UserSettingsController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $status = $request->query('status', 'active');

        if (! in_array($status, ['active', 'inactive', 'pending', 'all'], true)) {
            $status = 'active';
        }

        return Inertia::render('admin/settings/users/index', [
           'users' => User::query()
                ->with([
                    'clients:id,company_name,is_active',
                    'userType:id,user_type_name',
                    'companyWorkDetails:id,user_id,client_id,job_role,salary,travel_allowance,travel_allowance_currency,start_shift,end_shift',
                ])
            ->select('id', 'first_name', 'last_name', 'email', 'status', 'phone', 'mobile', 'client_id', 'user_type_id')
                ->when($status !== 'all', fn($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $normalizedSearch = mb_strtolower($search);
                $searchValue = '%' . $normalizedSearch . '%';

                $query->where(function ($query) use ($normalizedSearch, $searchValue) {
                    $query
                        ->whereRaw('LOWER(first_name) LIKE ?', [$searchValue])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchValue])
                        ->orWhereRaw("LOWER(first_name || ' ' || last_name) LIKE ?", [$searchValue])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$searchValue])
                        ->orWhereHas('clients', function ($query) use ($searchValue) {
                            $query->whereRaw('LOWER(company_name) LIKE ?', [$searchValue]);
                        });

                    if (in_array($normalizedSearch, ['active', 'inactive', 'pending'], true)) {
                        $query->orWhere('status', $normalizedSearch);
                    }
                });
            })
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString(),

            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'companies' => Client::query()->active()
            ->select('id', 'company_name', 'is_active')
            ->orderBy('company_name', 'asc')
            ->get(),

            'userTypes' => UserType::query()
            ->select('id', 'user_type_name')
            ->where('user_type_name', '!=', 'Superadmin')
            ->orderBy('id')
            ->get(),

        ]);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        abort_unless(
            in_array($user->status, ['active', 'inactive'], true),
            422,
            'Only active or inactive users can have their status changed.'
        );

        $wasInactive = $user->status === 'inactive';

        $user->update([
            'status' => $wasInactive ? 'pending' : 'inactive',
        ]);

        if ($wasInactive) {
            SendAccountAccessLink::dispatch($user->id)->afterCommit();
        }

        return $wasInactive ? to_route('settings.users.index', ['status' => 'pending']) : back();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'client_ids' => ['required', 'array', 'max:3'],
            'client_ids.*' => ['required', 'integer', Rule::exists('clients', 'id')->where('is_active', true)],
            'user_type_id' => [
                'required',
                Rule::exists('user_types', 'id')->where(fn($query) => $query->where('user_type_name', '!=', 'Superadmin')),
            ],
        ]);


        // User::create($validated + ['status' => 'active']);
        $clientIds = $validated['client_ids'];
        unset($validated['client_ids']);

        $user = User::create([
            ...$validated,
            'client_id' => $clientIds[0] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'status' => 'pending',
            'password' => Hash::make(Str::random(32)),
        ]);

        $user->clients()->sync($clientIds);

        SendAccountAccessLink::dispatch($user->id)->afterCommit();


        return to_route('settings.users.index', ['status' => 'pending']);
    }

    public function resendInvitation(User $user): RedirectResponse
    {
        abort_unless(
            $user->status === 'pending',
            422,
            'Invitations can only be resent to pending users.'
        );

        SendAccountAccessLink::dispatch($user->id)->afterCommit();

        return back();
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'client_ids' => ['required', 'array', 'max:3'],
            'work_detail' => ['nullable', 'array'],
            'work_detail.client_id' => ['required_with:work_detail', 'integer', Rule::exists('clients', 'id')->where('is_active', true)],
            'work_detail.salary' => ['required_with:work_detail', 'numeric', 'min:0'],
            'work_detail.travel_allowance' => ['required_with:work_detail', 'numeric', 'min:0'],
            'work_detail.travel_allowance_currency' => ['required_with:work_detail', 'string', 'size:3'],
            'work_detail.job_role' => ['nullable', 'string', 'max:255'],
            'work_detail.start_shift' => ['required_with:work_detail', 'date_format:H:i'],
            'work_detail.end_shift' => ['required_with:work_detail', 'date_format:H:i'],
            // Existing inactive assignments may be retained, but cannot be newly assigned.
            'client_ids.*' => ['required', 'integer', Rule::exists('clients', 'id')->where(
                fn ($query) => $query->where('is_active', true)
                    ->orWhereIn('id', $user->clients()->pluck('clients.id'))
            )],
            'user_type_id' => [
                'required',
                Rule::exists('user_types', 'id')->where(fn($query) => $query->where('user_type_name', '!=', 'Superadmin')),
            ],
        ]);

        $clientIds = $validated['client_ids'];
        $workDetail = $validated['work_detail'] ?? null;

        if (! empty($workDetail) && ! in_array((int) $workDetail['client_id'], array_map('intval', $clientIds), true)) {
            return back()->withErrors([
                'work_detail.client_id' => 'Select one of the assigned companies before saving work details.',
            ]);
        }

        unset($validated['client_ids'], $workDetail);

        $user->update([
            ...$validated,
            'client_id' => $clientIds[0] ?? null,
            'mobile' => $validated['mobile'] ?? null,
        ]);

        $inactiveIds = $user->clients()->where('clients.is_active', false)->pluck('clients.id')->all();
        $user->clients()->sync(array_unique([...$clientIds, ...$inactiveIds]));

        if ($workDetail) {

            $user->companyWorkDetails()->updateOrCreate(
                ['client_id' => $workDetail['client_id']],
                [
                    'salary' => $workDetail['salary'],
                    'travel_allowance' => $workDetail['travel_allowance'],
                    'travel_allowance_currency' => $workDetail['travel_allowance_currency'],
                    'job_role' => $workDetail['job_role'],
                    'start_shift' => $workDetail['start_shift'],
                    'end_shift' => $workDetail['end_shift'],
                ]
            );

        }

        return back();
    }
}
