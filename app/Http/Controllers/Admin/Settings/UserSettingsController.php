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
                ->with(['clients:id,company_name', 'userType:id,user_type_name'])
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
            'companies' => Client::query()
            ->select('id', 'company_name')
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
        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);

        return back();
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
            'client_ids.*' => ['required', 'exists:clients,id'],
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

        SendAccountAccessLink::dispatch($user->id);


        return to_route('settings.users.index', ['status' => 'pending']);
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
            'client_ids.*' => ['required', 'exists:clients,id'],
            'user_type_id' => [
                'required',
                Rule::exists('user_types', 'id')->where(fn($query) => $query->where('user_type_name', '!=', 'Superadmin')),
            ],
        ]);

        $clientIds = $validated['client_ids'];
        unset($validated['client_ids']);

        $user->update([
            ...$validated,
            'client_id' => $clientIds[0] ?? null,
            'mobile' => $validated['mobile'] ?? null,
        ]);

        $user->clients()->sync($clientIds);

        return back();
    }
}
