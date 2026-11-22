<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\AuthCode;
use Laravel\Passport\DeviceCode;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

class DeletePendingUser
{
    public function __construct(
        private ProfileFileCleanup $cleanup,
    ) {}

    public function handle(
        User $actor,
        User $target,
        bool $companyScoped = false,
    ): void {
        DB::transaction(function () use ($actor, $target, $companyScoped) {
            $user = User::query()
                ->whereKey($target->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                (int) $actor->id === (int) $user->id,
                403,
                'You cannot delete your own account here.',
            );

            abort_if(
                $user->userType?->user_type_name === 'Superadmin'
                    && $actor->userType?->user_type_name !== 'Superadmin',
                403,
                'Only a Superadmin may delete a Superadmin account.',
            );

            if ($companyScoped) {
                abort_unless(
                    $actor->client_id !== null
                        && (int) $user->client_id === (int) $actor->client_id,
                    403,
                    'This user does not belong to your company.',
                );

                if ($actor->userType?->user_type_name === 'Client Admin') {
                    abort_unless(
                        in_array(
                            $user->userType?->user_type_name,
                            ['Client Admin', 'Client Standard', 'Contractor'],
                            true,
                        ),
                        403,
                        'You cannot delete this account type.',
                    );

                    $companyId = (int) $actor->client_id;

                    $sharedWithAnotherCompany =
                        $user->clients()
                            ->where('clients.id', '!=', $companyId)
                            ->exists()
                        || $user->locationAssignments()
                            ->where('client_id', '!=', $companyId)
                            ->exists()
                        || $user->companyWorkDetails()
                            ->where('client_id', '!=', $companyId)
                            ->exists();

                    if ($sharedWithAnotherCompany) {
                        throw ValidationException::withMessages([
                            'delete_user' =>
                                'This account is shared with another company. '
                                .'Ask an SOS Admin to manage it.',
                        ]);
                    }
                }
            }

            if ($user->status !== 'pending') {
                throw ValidationException::withMessages([
                    'delete_user' => 'Only pending users can be deleted.',
                ]);
            }

            $hasBusinessHistory =
                $user->attendance()->exists()
                || DB::table('travel_allowances')
                    ->where('user_id', $user->id)
                    ->exists()
                || DB::table('client_save_requests')
                    ->where('user_id', $user->id)
                    ->exists()
                || DB::table('zoho_user_provisions')
                    ->where('user_id', $user->id)
                    ->exists()
                || DB::table('clients')
                    ->where('geofence_pinned_by', $user->id)
                    ->exists()
                || DB::table('sites')
                    ->where('geofence_pinned_by', $user->id)
                    ->exists();

            if ($hasBusinessHistory) {
                throw ValidationException::withMessages([
                    'delete_user' =>
                        'This user has business history or a Zoho provisioning '
                        .'record and cannot be permanently deleted.',
                ]);
            }

            if ($user->oauthApps()->exists()) {
                throw ValidationException::withMessages([
                    'delete_user' =>
                        'This user owns OAuth applications. '
                        .'Transfer or remove those applications first.',
                ]);
            }

            $deletionIds = [];

            foreach (array_filter([
                $user->profile_photo_path,
                $user->profile_banner_path,
            ]) as $path) {
                $deletionIds[] = $this->cleanup->remember(
                    $this->cleanup->imageDisk($path),
                    $path,
                );
            }

            $documents = DB::table('profile_documents')
                ->where('user_id', $user->id)
                ->get(['path']);

            foreach ($documents as $document) {
                $deletionIds[] = $this->cleanup->remember(
                    'profile_documents',
                    $document->path,
                );
            }

            $accessTokenIds = Token::query()
                ->where('user_id', $user->id)
                ->pluck('id');

            RefreshToken::query()
                ->whereIn('access_token_id', $accessTokenIds)
                ->delete();

            Token::query()
                ->where('user_id', $user->id)
                ->delete();

            AuthCode::query()
                ->where('user_id', $user->id)
                ->delete();

            DeviceCode::query()
                ->where('user_id', $user->id)
                ->delete();

            Password::broker()->deleteToken($user);

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)
                    ->delete();
            }

            $user->delete();

            DB::afterCommit(function () use ($deletionIds): void {
                foreach ($deletionIds as $id) {
                    $this->cleanup->attempt($id);
                }
            });
        });
    }
}
