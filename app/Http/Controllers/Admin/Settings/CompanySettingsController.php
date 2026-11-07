<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Site;
use Illuminate\Http\JsonResponse;

class CompanySettingsController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        return Inertia::render('admin/settings/company/index', [
            'companies' => Client::query()
                ->select([
                    'id',
                    'company_name',
                    'company_type',
                    'trade',
                    'industry',
                    'industry_description',
                    'phone',
                    'website',
                    'company_address',
                    'company_address_2',
                    'company_address_city',
                    'company_address_state',
                    'company_address_zip',
                    'company_address_country',
                    'is_active',
                    'geofence_latitude',
                    'geofence_longitude',
                    'geofence_pinned_at',
                    'geofence_radius_meters'
                ])
                ->withExists(Client::RELATED_RECORDS)
                ->when(
                    $request->user()->userType?->user_type_name === 'Client Admin',
                    fn ($query) => $query->whereKey($request->user()->client_id),
                )
                ->when($search !== '', function ($query) use ($search) {
                    $searchValue = '%' . mb_strtolower($search) . '%';

                    $query->where(function ($query) use ($searchValue) {
                        $query
                            ->whereRaw('LOWER(company_name) LIKE ?', [$searchValue])
                            ->orWhereRaw('LOWER(trade) LIKE ?', [$searchValue])
                            ->orWhereRaw('LOWER(industry) LIKE ?', [$searchValue])
                            ->orWhereRaw('LOWER(company_address_state) LIKE ?', [$searchValue]);
                    });
                })
                ->orderBy('company_name')
                ->paginate(10)
                ->withQueryString()
                ->through(function (Client $company) {
                    $hasRelatedRecords = collect(Client::RELATED_RECORDS)
                        ->contains(fn (string $relation): bool =>
                            (bool) $company->getAttribute(
                                \Illuminate\Support\Str::snake($relation).'_exists'
                            )
                        );

                    $company->setAttribute('can_delete', ! $hasRelatedRecords);

                    return $company;
                }),

                'sites' => Site::query()
                ->select([
                    'id',
                    'client_id',
                    'site_name',
                    'is_active',
                    'street',
                    'city',
                    'state',
                    'postcode',
                    'country',
                    'geofence_latitude',
                    'geofence_longitude',
                    'geofence_pinned_at',
                    'geofence_radius_meters'
                ])
                ->with('company:id,company_name')
                ->when(
                    $request->user()->userType?->user_type_name === 'Client Admin',
                    fn ($query) => $query->where(
                        'client_id',
                        $request->user()->client_id,
                    ),
                )
                ->orderBy('site_name')
                ->orderBy('id')
                ->get(),

            'default_max_distance_meters' => (int) config(
                'attendance.check_in_radius_meters',
                100,
            ),
            'filters' => [
                'search' => $search,
            ],
            'can_manage_companies' => in_array(
                $request->user()->userType?->user_type_name,
                ['Superadmin', 'SOS Admin'],
                true,
            ),
        ]);
    }

    public function companyLocation(
        Request $request,
        Client $client,
    ): JsonResponse {
        abort_unless(
            in_array(
                $request->user()->userType?->user_type_name,
                ['Superadmin', 'SOS Admin'],
                true,
            ),
            403,
        );

        return response()->json([
            'company' => $client->only([
                'id',
                'company_name',
                'is_active',
                'company_address',
                'company_address_2',
                'company_address_city',
                'company_address_state',
                'company_address_zip',
                'company_address_country',
                'geofence_latitude',
                'geofence_longitude',
                'geofence_pinned_at',
                'geofence_radius_meters'
            ]),
        ]);
    }

    private function maxDistanceInMeters(array $validated): int
    {
        $value = (float) $validated['max_distance'];

        $metres = $validated['max_distance_unit'] === 'km'
            ? $value * 1000
            : $value;

        if (
            ! is_finite($metres)
            || $metres < 1
            || $metres > 4294967295
            || abs($metres - round($metres)) > 0.000001
        ) {
            throw ValidationException::withMessages([
                'max_distance' =>
                    'Enter a distance equivalent to a whole number of metres, starting from 1 metre.',
            ]);
        }

        return (int) round($metres);
    }

    public function pinCompanyLocation(
        Request $request,
        Client $client,
    ): RedirectResponse {
        abort_unless(
            in_array(
                $request->user()->userType?->user_type_name,
                ['Superadmin', 'SOS Admin'],
                true,
            ),
            403,
        );

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'max_distance' => ['required', 'numeric', 'gt:0'],
            'max_distance_unit' => ['required', 'in:m,km'],
        ]);

        $radiusMeters = $this->maxDistanceInMeters($validated);

        DB::transaction(function () use (
            $request,
            $client,
            $validated,
            $radiusMeters,
        ): void {
            $company = Client::query()
                ->whereKey($client->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $company->is_active) {
                throw ValidationException::withMessages([
                    'location' => 'Activate the company before pinning its location.',
                ]);
            }

            $company->forceFill([
                'geofence_latitude' => (float) $validated['latitude'],
                'geofence_longitude' => (float) $validated['longitude'],
                'geofence_radius_meters' => $radiusMeters,
                'geofence_pinned_at' => now(),
                'geofence_pinned_by' => $request->user()->id,
                'geofence_address_hash' => null,
                'geofence_geocoded_at' => null,
            ])->save();
        });

        return back();
    }

    public function updateCompany(
        Request $request,
        Client $client,
    ): RedirectResponse {
        abort_unless(
            in_array(
                $request->user()->userType?->user_type_name,
                ['Superadmin', 'SOS Admin'],
                true,
            ),
            403,
        );

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_type' => ['nullable', 'string', 'max:100'],
            'trade' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'industry_description' => ['nullable', 'string', 'max:10000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_address_2' => ['nullable', 'string', 'max:255'],
            'company_address_city' => ['nullable', 'string', 'max:255'],
            'company_address_state' => ['nullable', 'string', 'max:255'],
            'company_address_zip' => ['nullable', 'string', 'max:20'],
            'company_address_country' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($client, $validated): void {
            $company = Client::query()
                ->whereKey($client->id)
                ->lockForUpdate()
                ->firstOrFail();

            $company->fill($validated);

            $addressChanged = $company->isDirty([
                'company_address',
                'company_address_2',
                'company_address_city',
                'company_address_state',
                'company_address_zip',
                'company_address_country',
            ]);

            if ($addressChanged) {
                $company->forceFill([
                    'geofence_latitude' => null,
                    'geofence_longitude' => null,
                    'geofence_address_hash' => null,
                    'geofence_geocoded_at' => null,
                    'geofence_pinned_at' => null,
                    'geofence_pinned_by' => null,
                ]);
            }

            $company->save();
        });

        return back();
    }

    public function destroy(Client $client): RedirectResponse
    {
        DB::transaction(function () use ($client) {
            $company = Client::query()
                ->lockForUpdate()
                ->findOrFail($client->id);

            if ($company->hasRelatedRecords()) {
                throw ValidationException::withMessages([
                    'company' =>
                        'This company has related records. Deactivate it instead.',
                ]);
            }

            $company->delete();
        });

        return back();
    }

    public function pinSiteLocation(
        Request $request,
        Site $site,
    ): RedirectResponse {
        abort_unless(
            in_array(
                $request->user()->userType?->user_type_name,
                ['Superadmin', 'SOS Admin'],
                true,
            ),
            403,
        );

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        DB::transaction(function () use (
            $request,
            $site,
            $validated,
        ): void {
            $company = Client::query()
                ->whereKey($site->client_id)
                ->lockForUpdate()
                ->firstOrFail();

            $record = Site::query()
                ->whereKey($site->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $company->is_active || ! $record->is_active) {
                throw ValidationException::withMessages([
                    'location' => 'Activate the company and site before pinning its location.',
                ]);
            }

            $record->forceFill([
                'geofence_latitude' => (float) $validated['latitude'],
                'geofence_longitude' => (float) $validated['longitude'],
                'geofence_pinned_at' => now(),
                'geofence_pinned_by' => $request->user()->id,
                'geofence_address_hash' => null,
                'geofence_geocoded_at' => null,
            ])->save();
        });

        return back();
    }

    public function updateSite(Request $request, Site $site): RedirectResponse
    {
        abort_unless(
            in_array(
                $request->user()->userType?->user_type_name,
                ['Superadmin', 'SOS Admin'],
                true,
            ),
            403,
        );

        $validated = $request->validate([
            'site_name' => ['nullable', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($site, $validated): void {
            // Keep the same company-before-site locking order used elsewhere.
            Client::query()
                ->whereKey($site->client_id)
                ->lockForUpdate()
                ->firstOrFail();

            $record = Site::query()
                ->whereKey($site->id)
                ->lockForUpdate()
                ->firstOrFail();

            $record->fill($validated);

            $addressChanged = $record->isDirty([
                'street',
                'city',
                'state',
                'postcode',
                'country',
            ]);

            if ($addressChanged) {
                $record->forceFill([
                    'geofence_latitude' => null,
                    'geofence_longitude' => null,
                    'geofence_address_hash' => null,
                    'geofence_geocoded_at' => null,
                    'geofence_pinned_at' => null,
                    'geofence_pinned_by' => null,
                ]);
            }

            $record->save();
        });

        return back();
    }

    public function updateSiteStatus(Request $request, Site $site,): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $site->is_active = $validated['is_active'];
        $site->save();

        return back();
    }

    public function updateStatus(
        Request $request,
        Client $client,
    ): RedirectResponse {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($client, $validated) {
            $company = Client::query()
                ->lockForUpdate()
                ->findOrFail($client->id);

            if (! $company->hasRelatedRecords()) {
                throw ValidationException::withMessages([
                    'company' =>
                        'This company has no related records. Use Delete instead.',
                ]);
            }

            $isActive = (bool) $validated['is_active'];

            if (! $isActive && $company->attendances()
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->exists()) {
                throw ValidationException::withMessages([
                    'company' =>
                        'Finish all open attendance shifts before deactivating this company.',
                ]);
            }

            $company->is_active = $isActive;
            $company->save();
        });

        return back();
    }



}
