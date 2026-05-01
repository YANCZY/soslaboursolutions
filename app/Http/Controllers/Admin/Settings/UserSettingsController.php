<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Illuminate\Http\Request;


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
        ]);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);

        return back();
    }
}
