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
use App\Services\UserLocationService;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $profile = $request->user()->profile()->firstOrCreate([]);

       $locations = app(UserLocationService::class)
            ->options($request->user());

        $requestedLocationId = $request->integer(
            'location_assignment_id',
        );

        $selectedLocation = $locations->firstWhere(
            'id',
            $requestedLocationId,
        ) ?? $locations->first();

        $selectedLocationId = $selectedLocation['id'] ?? null;
        $selectedCompanyId = $selectedLocation['client_id'] ?? null;

        $workDetail = $selectedLocation
            ? $request->user()
                ->companyWorkDetails()
                ->where('client_id', $selectedLocation['client_id'])
                ->where('site_id', $selectedLocation['site_id'])
                ->first()
            : null;

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'profile' => $profile,
            'locations' => $locations,
            'workDetail' => $workDetail,
            'workDetails' => $request->user()->companyWorkDetails()->get(),
            'selectedLocationId' => $selectedLocationId,
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit', [
            'location_assignment_id' =>
                $validated['location_assignment_id'] ?? null,
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
