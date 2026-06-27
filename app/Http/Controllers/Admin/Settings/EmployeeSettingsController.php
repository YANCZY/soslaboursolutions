<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\EmailSending\SendAccountAccessLink;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmployeeSettingsController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $status = $request->query('status', 'active');

        if (! in_array($status, ['active', 'inactive', 'pending', 'all'], true)) {
            $status = 'active';
        }

        return Inertia::render('admin/settings/employee/index', [
            'employees' => User::query()
                ->with('userType:id,user_type_name')
                ->select('id', 'first_name', 'last_name', 'email', 'status', 'phone', 'mobile', 'client_id', 'user_type_id')
                ->where('client_id', $request->user()->client_id)
                ->when($status !== 'all', fn($query) => $query->where('status', $status))
                ->when($search !== '', function ($query) use ($search) {
                    $searchValue = '%'.mb_strtolower($search).'%';

                    $query->where(function ($query) use ($searchValue) {
                        $query
                            ->whereRaw('LOWER(first_name) LIKE ?', [$searchValue])
                            ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchValue])
                            ->orWhereRaw("LOWER(first_name || ' ' || last_name) LIKE ?", [$searchValue])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$searchValue])
                            ->orWhereHas('userType', function ($query) use ($searchValue) {
                                $query->whereRaw('LOWER(user_type_name) LIKE ?', [$searchValue]);
                            });
                    });
                })
                ->orderBy('id')
                ->paginate(10)
                ->withQueryString(),

            'filters' => [
                'search' => $search,
                'status' => $status,
            ],

            'userTypes' => UserType::query()
                ->select('id', 'user_type_name')
                ->whereIn('user_type_name', ['Client Admin', 'Client Standard', 'Contractor'])
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'user_type_id' => [
                'required',
                Rule::exists('user_types', 'id')->where(fn ($query) => $query->whereIn('user_type_name', [
                    'Client Admin',
                    'Client Standard',
                    'Contractor',
                ])),
            ],
        ]);

        $user = User::create([
            ...$validated,
            'client_id' => $request->user()->client_id,
            'status' => 'pending',
            'password' => Hash::make(Str::random(32)),
        ]);

        SendAccountAccessLink::dispatch($user->id);

        return back();
    }

    public function toggleStatus(User $user, Request $request): RedirectResponse
    {
        abort_unless($user->client_id === $request->user()->client_id, 403);

        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);

        return back();
    }
}
