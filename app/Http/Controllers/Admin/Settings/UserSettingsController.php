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
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;



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
                ->with([
                    'clients:id,company_name,is_active',
                    'userType:id,user_type_name',
                    'companyWorkDetails:id,user_id,client_id,site_id,job_role,salary,travel_allowance,travel_allowance_currency,start_shift,end_shift',                    'locationAssignments.company:id,company_name,is_active',
                    'locationAssignments.site:id,site_name,is_active',
                 ])
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
            ->active()
            ->select('id', 'company_name', 'is_active')
            ->orderBy('company_name')
            ->get(),

        'assignment_options' => Client::query()
            ->active()
            ->select('id', 'company_name')
            ->with([
                'sites' => fn ($query) => $query
                    ->where('is_active', true)
                    ->select('id', 'client_id', 'site_name')
                    ->orderBy('site_name')
                    ->orderBy('id'),
            ])
            ->orderBy('company_name')
            ->get()
            ->flatMap(function (Client $company) {
                $options = [[
                    'key' => 'company:'.$company->id,
                    'client_id' => $company->id,
                    'site_id' => null,
                    'label' => $company->company_name,
                ]];

                foreach ($company->sites as $site) {
                    $siteName = trim((string) $site->site_name)
                        ?: $company->company_name;

                    $options[] = [
                        'key' => 'site:'.$site->id,
                        'client_id' => $company->id,
                        'site_id' => $site->id,
                        'label' => $company->company_name.' - '.$siteName,
                    ];
                }

                return $options;
            })
            ->values(),
            'userTypes' => UserType::query()
            ->select('id', 'user_type_name')
            ->where('user_type_name', '!=', 'Superadmin')
            ->orderBy('id')
            ->get(),

        ]);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        abort_unless(
            in_array($user->status, ['active', 'inactive'], true),
            422,
            'Only active or inactive users can have their status changed.'
        );

        $wasInactive = $user->status === 'inactive';

        $user->update([
            'status' => $wasInactive ? 'pending' : 'inactive',
        ]);

        if ($wasInactive) {
            SendAccountAccessLink::dispatch($user->id)->afterCommit();
        }

        return $wasInactive ? to_route('settings.users.index', ['status' => 'pending']) : back();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_ids' => ['required', 'array', 'min:1', 'max:3'],
            'client_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('clients', 'id')->where('is_active', true),
            ],

            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*' => ['required', 'array:client_id,site_id'],
            'assignments.*.client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id'),
            ],
            'assignments.*.site_id' => [
                'present',
                'nullable',
                'integer',
                Rule::exists('sites', 'id'),
            ],
        ]);


       DB::transaction(function () use ($validated) {
            $assignments = collect($validated['assignments'])
                ->map(fn ($assignment) => [
                    'client_id' => (int) $assignment['client_id'],
                    'site_id' => isset($assignment['site_id'])
                        ? (int) $assignment['site_id']
                        : null,
                ]);

            $companyIds = $assignments
                ->pluck('client_id')
                ->unique()
                ->sort()
                ->values()
                ->all();

            $submittedCompanyIds = collect($validated['client_ids'])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($companyIds !== $submittedCompanyIds) {
                throw ValidationException::withMessages([
                    'assignments' => 'Company membership must match the selected locations.',
                ]);
            }

            $keys = $assignments->map(
                fn ($assignment) =>
                    $assignment['client_id'].':'.($assignment['site_id'] ?? 'main')
            );

            if ($keys->unique()->count() !== $keys->count()) {
                throw ValidationException::withMessages([
                    'assignments' => 'The same company or site cannot be selected twice.',
                ]);
            }

            $companies = Client::query()
                ->whereIn('id', $companyIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $sites = Site::query()
                ->whereIn(
                    'id',
                    $assignments->pluck('site_id')->filter()->unique()->all(),
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($assignments as $assignment) {
                $company = $companies->get($assignment['client_id']);

                if (! $company || ! $company->is_active) {
                    throw ValidationException::withMessages([
                        'assignments' => 'A selected company is inactive or unavailable.',
                    ]);
                }

                if ($assignment['site_id'] !== null) {
                    $site = $sites->get($assignment['site_id']);

                    if (
                        ! $site ||
                        ! $site->is_active ||
                        (int) $site->client_id !== $assignment['client_id']
                    ) {
                        throw ValidationException::withMessages([
                            'assignments' => 'A selected site is inactive or does not belong to its company.',
                        ]);
                    }
                }
            }

            $userData = $validated;
            unset($userData['client_ids'], $userData['assignments']);

            $user = User::create([
                ...$userData,
                'client_id' => $companyIds[0],
                'mobile' => $userData['mobile'] ?? null,
                'status' => 'pending',
                'password' => Hash::make(Str::random(32)),
            ]);

            $user->clients()->sync($companyIds);
            $user->locationAssignments()->createMany($assignments->all());

            SendAccountAccessLink::dispatch($user->id)->afterCommit();
        });

        return to_route('settings.users.index', ['status' => 'pending']);


    }

    public function resendInvitation(User $user): RedirectResponse
    {
        abort_unless(
            $user->status === 'pending',
            422,
            'Invitations can only be resent to pending users.'
        );

        SendAccountAccessLink::dispatch($user->id)->afterCommit();

        return back();
    }

    public function update(Request $request, User $user): RedirectResponse
    {

        abort_if(
            $user->userType?->user_type_name === 'Superadmin'
                && $request->user()->userType?->user_type_name !== 'Superadmin',
            403,
            'Only a Superadmin may edit a Superadmin account.',
        );

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'user_type_id' => [
                'required',
                'integer',
                Rule::exists('user_types', 'id')->where(
                    fn ($query) => $query
                        ->where('user_type_name', '!=', 'Superadmin')
                        ->orWhere('id', $user->user_type_id),
                ),
            ],

            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*' => ['required', 'array:client_id,site_id'],
            'assignments.*.client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id'),
            ],
            'assignments.*.site_id' => [
                'present',
                'nullable',
                'integer',
                Rule::exists('sites', 'id'),
            ],

            'work_detail' => ['nullable', 'array'],
            'work_detail.client_id' => [
                'required_with:work_detail',
                'integer',
                Rule::exists('clients', 'id'),
            ],
            'work_detail.site_id' => [
                'nullable',
                'integer',
                Rule::exists('sites', 'id'),
            ],
            'work_detail.salary' => [
                'required_with:work_detail', 'numeric', 'min:0',
            ],
            'work_detail.travel_allowance' => [
                'required_with:work_detail', 'numeric', 'min:0',
            ],
            'work_detail.travel_allowance_currency' => [
                'required_with:work_detail', 'string', 'size:3',
            ],
            'work_detail.job_role' => ['nullable', 'string', 'max:255'],
            'work_detail.start_shift' => [
                'required_with:work_detail', 'date_format:H:i',
            ],
            'work_detail.end_shift' => [
                'required_with:work_detail', 'date_format:H:i',
            ],
        ]);

        DB::transaction(function () use ($validated, $user) {
            $user = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = $user->locationAssignments()->get();

            $requested = collect($validated['assignments'])
                ->map(fn ($assignment) => [
                    'client_id' => (int) $assignment['client_id'],
                    'site_id' => isset($assignment['site_id'])
                        ? (int) $assignment['site_id']
                        : null,
                ]);

            $keyFor = fn ($assignment) =>
                $assignment['client_id'].':'.
                ($assignment['site_id'] ?? 'main');

            if (
                $requested->map($keyFor)->unique()->count()
                !== $requested->count()
            ) {
                throw ValidationException::withMessages([
                    'assignments' => 'A location cannot be selected twice.',
                ]);
            }

            $legacyCompanyIds = $user->clients()
                ->pluck('clients.id');

            $companyIdsToLock = $requested->pluck('client_id')
                ->merge($existing->pluck('client_id'))
                ->merge($legacyCompanyIds)
                ->unique()
                ->sort()
                ->values();

            $companies = Client::query()
                ->whereIn('id', $companyIdsToLock)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $siteIdsToLock = $requested->pluck('site_id')
                ->merge($existing->pluck('site_id'))
                ->filter()
                ->unique();

            $sites = Site::query()
                ->whereIn('id', $siteIdsToLock)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $isAvailable = function ($assignment) use ($companies, $sites) {
                $company = $companies->get($assignment['client_id']);

                if (! $company || ! $company->is_active) {
                    return false;
                }

                if ($assignment['site_id'] === null) {
                    return true;
                }

                $site = $sites->get($assignment['site_id']);

                return $site
                    && $site->is_active
                    && (int) $site->client_id
                        === (int) $assignment['client_id'];
            };

            $existingByKey = $existing->keyBy($keyFor);

            foreach ($requested as $assignment) {
                $site = $assignment['site_id'] !== null
                    ? $sites->get($assignment['site_id'])
                    : null;

                if (
                    $assignment['site_id'] !== null &&
                    (
                        ! $site ||
                        (int) $site->client_id
                            !== $assignment['client_id']
                    )
                ) {
                    throw ValidationException::withMessages([
                        'assignments' => 'A site must belong to its selected company.',
                    ]);
                }

                if (
                    ! $isAvailable($assignment) &&
                    ! $existingByKey->has($keyFor($assignment))
                ) {
                    throw ValidationException::withMessages([
                        'assignments' => 'New assignments require an active company and site.',
                    ]);
                }
            }

            // Preserve inactive assignments even if omitted by the browser.
            $retainedInactive = $existing
                ->filter(fn ($assignment) => ! $isAvailable($assignment))
                ->map(fn ($assignment) => [
                    'client_id' => (int) $assignment->client_id,
                    'site_id' => $assignment->site_id !== null
                        ? (int) $assignment->site_id
                        : null,
                ]);

            $finalAssignments = $requested
                ->concat($retainedInactive)
                ->unique($keyFor)
                ->values();

            $inactiveLegacyCompanyIds = $legacyCompanyIds->filter(
                fn ($id) => ! $companies->get($id)?->is_active
            );

            $finalCompanyIds = $finalAssignments->pluck('client_id')
                ->merge($inactiveLegacyCompanyIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (count($finalCompanyIds) > 3) {
                throw ValidationException::withMessages([
                    'assignments' => 'You can assign up to 3 companies.',
                ]);
            }

            $workDetail = $validated['work_detail'] ?? null;

            if ($workDetail) {
                $workLocation = [
                    'client_id' => (int) $workDetail['client_id'],
                    'site_id' => isset($workDetail['site_id'])
                        ? (int) $workDetail['site_id']
                        : null,
                ];

                $isAssigned = $finalAssignments->contains(
                    fn ($assignment) =>
                        $assignment['client_id'] === $workLocation['client_id']
                        && $assignment['site_id'] === $workLocation['site_id']
                );

                if (! $isAssigned || ! $isAvailable($workLocation)) {
                    throw ValidationException::withMessages([
                        'work_detail.client_id' =>
                            'Select an active assigned company or site.',
                    ]);
                }
            }

            $userData = $validated;
            unset($userData['assignments'], $userData['work_detail']);

            $primaryCompanyId = in_array(
                (int) $user->client_id,
                $finalCompanyIds,
                true,
            )
                ? $user->client_id
                : ($finalCompanyIds[0] ?? null);

            $user->update([
                ...$userData,
                'client_id' => $primaryCompanyId,
            ]);

            $retainedAssignmentIds = [];

            foreach ($finalAssignments as $assignment) {
                $record = $user->locationAssignments()->firstOrCreate(
                    $assignment
                );

                $retainedAssignmentIds[] = $record->id;
            }

            // Only explicitly deselected active assignments are removed.
            $user->locationAssignments()
                ->whereNotIn('id', $retainedAssignmentIds)
                ->delete();

            $user->clients()->sync($finalCompanyIds);

            if ($workDetail) {
                $user->companyWorkDetails()->updateOrCreate(
                    [
                        'client_id' => (int) $workDetail['client_id'],
                        'site_id' => isset($workDetail['site_id'])
                            ? (int) $workDetail['site_id']
                            : null,
                    ],
                    [
                        'salary' => $workDetail['salary'],
                        'travel_allowance' => $workDetail['travel_allowance'],
                        'travel_allowance_currency' =>
                            $workDetail['travel_allowance_currency'],
                        'job_role' => $workDetail['job_role'] ?? null,
                        'start_shift' => $workDetail['start_shift'],
                        'end_shift' => $workDetail['end_shift'],
                    ],
                );
            }
        });

        return back();
    }
}
