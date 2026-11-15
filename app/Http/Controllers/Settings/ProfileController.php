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
use App\Models\User;
use App\Services\ProfileFileCleanup;
use Illuminate\Support\Facades\DB;

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
            'documents' => DB::table('profile_documents')
                ->where('user_id', $request->user()->id)
                ->latest('id')
                ->get([
                    'id',
                    'category',
                    'original_name',
                    'mime_type',
                    'size',
                ])
                ->map(fn ($document) => [
                    'id' => $document->id,
                    'category' => $document->category,
                    'original_name' => $document->original_name,
                    'mime_type' => $document->mime_type,
                    'size' => $document->size,
                    'url' => route('profile.documents.show', $document->id),
                ]),
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
    public function destroy(ProfileDeleteRequest $request,ProfileFileCleanup $cleanup,): RedirectResponse
    {
        $userId = $request->user()->id;

        $deletionIds = DB::transaction(function () use (
            $userId,
            $cleanup,
        ) {
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($userId);

            $ids = [];

            foreach (array_filter([
                $user->profile_photo_path,
                $user->profile_banner_path,
            ]) as $path) {
                $ids[] = $cleanup->remember(
                    $cleanup->imageDisk($path),
                    $path,
                );
            }

            $documents = DB::table('profile_documents')
                ->where('user_id', $userId)
                ->get(['path']);

            foreach ($documents as $document) {
                $ids[] = $cleanup->remember(
                    'profile_documents',
                    $document->path,
                );
            }

            $user->delete();

            return $ids;
        });

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        foreach ($deletionIds as $id) {
            $cleanup->attempt($id);
        }

        return redirect('/');
    }
}
