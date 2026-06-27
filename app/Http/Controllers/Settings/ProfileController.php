<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $profile = $request->user()->profile()->firstOrCreate([]);

        $companies = $request->user()
            ->clients()
            ->select('clients.id', 'clients.company_name')
            ->orderBy('company_name')
            ->get();

        $selectedCompanyId = $request->integer('client_id') ?: $companies->first()?->id;

        $workDetail = $selectedCompanyId
            ? $request->user()
            ->companyWorkDetails()
            ->with('client:id,company_name')
            ->where('client_id', $selectedCompanyId)
            ->first()
            : null;

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'profile' => $profile,
            'companies' => $companies,
            'workDetail' => $workDetail,
            'workDetails' => $request->user()->companyWorkDetails()->get(),
            'selectedCompanyId' => $selectedCompanyId,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {

        $validated = $request->validated();

        $request->user()->fill(Arr::only($validated, [
            'first_name',
            'last_name',
            'email',
            'phone',
            'mobile',
        ]));

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        $request->user()->companyWorkDetails()->updateOrCreate(
            ['client_id' => $validated['client_id']],
            Arr::only($validated, [
                'job_role',
                'travel_allowance',
                'travel_allowance_currency',
                'salary',
                'start_shift',
                'end_shift',
            ])
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit', [
            'client_id' => $validated['client_id'],
        ]);
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete($user->id);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
