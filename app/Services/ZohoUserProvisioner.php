<?php

namespace App\Services;

use App\Jobs\EmailSending\SendAccountAccessLink;
use App\Models\Client;
use App\Models\Site;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ZohoUserProvisioner
{
    public function create(array $input): array
    {
        $data = [
            'source_type' => $input['source_type'],
            'source_id' => $input['source_id'],
            'first_name' => $input['first_name'] ?? '',
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'phone' => $input['phone'] ?? null,
            'profile' => $input['profile'],
            'company_id' => (int) $input['company_id'],
            'site_id' => isset($input['site_id'])
                ? (int) $input['site_id']
                : null,
            'site' => isset($input['site'])
                ? [
                    'site_name' => $input['site']['site_name'] ?? null,
                    'street' => $input['site']['street'],
                    'city' => $input['site']['city'],
                    'state' => $input['site']['state'],
                    'postcode' => $input['site']['postcode'],
                    'country' => $input['site']['country'],
                ]
                : null,
        ];

        $hash = hash(
            'sha256',
            json_encode($data, JSON_THROW_ON_ERROR),
        );

        try {
            return DB::transaction(function () use ($data, $hash): array {
                if ($result = $this->replay($data, $hash)) {
                    return $result;
                }

                $company = Client::query()
                    ->whereKey($data['company_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $company || ! $company->is_active) {
                    $this->invalid(
                        'company_id',
                        'The selected company does not exist or is inactive.',
                    );
                }

                // Another request may have completed while we waited
                // for the parent company lock.
                if ($result = $this->replay($data, $hash)) {
                    return $result;
                }

                if ($this->emailExists($data['email'])) {
                    $this->invalid(
                        'email',
                        'This email already belongs to a portal user. '
                        .'Use an explicit account-linking or assignment process.',
                    );
                }

                $userType = UserType::query()
                    ->where('user_type_name', $data['profile'])
                    ->first();

                if (! $userType) {
                    $this->invalid(
                        'profile',
                        'This profile is not configured in the portal.',
                    );
                }

                [$site, $siteCreated] = $this->resolveSite($company, $data);

                $user = User::query()->create([
                    // first_name is NOT NULL in the existing schema.
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'mobile' => null,
                    'user_type_id' => $userType->id,
                    'client_id' => $company->id,
                    'status' => 'pending',
                    'password' => Hash::make(Str::random(64)),
                ]);

                $user->clients()->attach($company->id);

                $assignment = $user->locationAssignments()->create([
                    'client_id' => $company->id,
                    'site_id' => $site?->id,
                ]);

                $response = [
                    'id' => $user->id,
                    'status' => $user->status,
                    'profile' => $data['profile'],
                    'company_id' => $company->id,
                    'site_id' => $site?->id,
                    'location_assignment_id' => $assignment->id,
                    'site_created' => $siteCreated,
                    'invitation_status' => 'queued',
                ];

                DB::table('zoho_user_provisions')->insert([
                    'source_type' => $data['source_type'],
                    'source_id' => $data['source_id'],
                    'request_hash' => $hash,
                    'user_id' => $user->id,
                    'response' => json_encode(
                        $response,
                        JSON_THROW_ON_ERROR,
                    ),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // This connection inserts the job into the same database
                // transaction. The worker processes it after commit.
                Bus::dispatch(
                    (new SendAccountAccessLink($user->id))
                        ->onConnection('user-invitations')
                        ->onQueue('default')
                        ->beforeCommit(),
                );

                return [
                    'created' => true,
                    'data' => $response,
                ];
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            // Handle concurrent requests for the same Zoho record,
            // including requests that supplied different companies.
            if ($result = $this->replay($data, $hash)) {
                return $result;
            }

            // Handle concurrent creation using the same email.
            if ($this->emailExists($data['email'])) {
                $this->invalid(
                    'email',
                    'This email already belongs to a portal user.',
                );
            }

            throw $exception;
        }
    }

    private function replay(array $data, string $hash): ?array
    {
        $record = DB::table('zoho_user_provisions')
            ->where('source_type', $data['source_type'])
            ->where('source_id', $data['source_id'])
            ->first();

        if (! $record) {
            return null;
        }

        abort_if(
            ! hash_equals($record->request_hash, $hash),
            409,
            'This Zoho record was already provisioned with different data. '
            .'Use an explicit user-update or assignment endpoint.',
        );

        return [
            'created' => false,
            'data' => json_decode(
                $record->response,
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        ];
    }

    private function resolveSite(Client $company, array $data): array
    {
        if ($data['site_id'] !== null) {
            $site = $company->sites()
                ->whereKey($data['site_id'])
                ->lockForUpdate()
                ->first();

            if (! $site || ! $site->is_active) {
                $this->invalid(
                    'site_id',
                    'The site is inactive, unavailable, or belongs to '
                    .'another company.',
                );
            }

            return [$site, false];
        }

        if ($data['site'] === null) {
            return [null, false];
        }

        $address = $data['site'];
        $key = $this->addressKey($address);

        // The company is already locked. Your existing site API also
        // locks the parent company before creating a site.
        $matches = $company->sites()
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->filter(
                fn (Site $site): bool =>
                    $this->addressKey($site->only([
                        'street',
                        'city',
                        'state',
                        'postcode',
                        'country',
                    ])) === $key,
            )
            ->values();

        if ($matches->count() > 1) {
            $this->invalid(
                'site',
                'Multiple sites match this address. '
                .'Resolve the duplicate records or provide a specific site_id.',
            );
        }

        if ($matches->isNotEmpty()) {
            $site = $matches->first();

            if (! $site->is_active) {
                $this->invalid(
                    'site',
                    'A site already exists at this address but is inactive. '
                    .'Reactivate or review it before assigning a user.',
                );
            }

            return [$site, false];
        }

        $site = $company->sites()->create($address);

        return [$site, true];
    }

    private function addressKey(array $address): string
    {
        $parts = [];

        foreach (['street', 'city', 'state', 'postcode', 'country'] as $field) {
            $parts[$field] = Str::lower(
                Str::squish((string) ($address[$field] ?? '')),
            );
        }

        $parts['country'] = match ($parts['country']) {
            'au', 'aus', 'australia' => 'australia',
            default => $parts['country'],
        };

        if ($parts['country'] === 'australia') {
            $parts['state'] = match ($parts['state']) {
                'new south wales', 'nsw' => 'nsw',
                'victoria', 'vic' => 'vic',
                'queensland', 'qld' => 'qld',
                'south australia', 'sa' => 'sa',
                'western australia', 'wa' => 'wa',
                'tasmania', 'tas' => 'tas',
                'northern territory', 'nt' => 'nt',
                'australian capital territory', 'act' => 'act',
                default => $parts['state'],
            };
        }

        return json_encode($parts, JSON_THROW_ON_ERROR);
    }

    private function emailExists(string $email): bool
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->exists();
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
