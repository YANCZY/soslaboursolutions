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
                    'company_address_country',
                    'is_active',
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
