<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\UserType;
use Illuminate\Support\Str;



class UserSettingsController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        return Inertia::render('admin/settings/users/index', [
           'users' => User::query()
            ->with('client:id,company_name')
            ->select('id', 'first_name', 'last_name', 'email', 'status', 'phone', 'mobile', 'client_id')
            ->when($search !== '', function ($query) use ($search) {
                $normalizedSearch = mb_strtolower($search);
                $searchValue = '%' . $normalizedSearch . '%';

                $query->where(function ($query) use ($normalizedSearch, $searchValue) {
                    $query
                        ->whereRaw('LOWER(first_name) LIKE ?', [$searchValue])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchValue])
                        ->orWhereRaw("LOWER(first_name || ' ' || last_name) LIKE ?", [$searchValue])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$searchValue]);

                    if (in_array($normalizedSearch, ['active', 'inactive'], true)) {
                        $query->orWhere('status', $normalizedSearch);
                    }
                });
            })
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString(),

            'filters' => [
                'search' => $search,
            ],
            'companies' => Client::query()
            ->select('id', 'company_name')
            ->orderBy('company_name', 'asc')
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
            'client_id' => 'nullable|exists:clients,id',
        ]);

           $adminUserType = UserType::query()->firstOrCreate([
                'user_type_name' => 'Admin',
            ]);

        // User::create($validated + ['status' => 'active']);
        User::create([
           ...$validated,
            'mobile' => $validated['mobile'] ?? null,
            'status' => 'pending',
            'password'=> Hash::make(Str::random(32)),
            'user_type_id' => $adminUserType->id,
        ]);

        // $user->sendEmailVerificationNotification();
        $status = Password::sendResetLink(['email' => $validated['email']]);


        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors([
                'email' => __($status),
            ]);
        }

        return back();
    }
}
