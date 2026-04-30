<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;


class UserSettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/settings/users/index', [
            'users' => User::query()
            ->with('client:id,company_name')
            ->select('id', 'first_name', 'last_name', 'email', 'status', 'phone', 'mobile', 'client_id')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString(),
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
