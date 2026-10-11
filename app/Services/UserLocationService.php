<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Site;
use App\Models\User;
use App\Models\UserLocationAssignment;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class UserLocationService
{
    public static function label(?Client $company, ?Site $site): string
    {
        $companyName = $company?->company_name ?? '';

        if (! $site) {
            return $companyName;
        }

        $siteName = trim((string) $site->site_name) ?: $companyName;

        return $companyName.' - '.$siteName;
    }

    public function options(User $user): Collection
    {
        $workDetails = $user->companyWorkDetails()
        ->get()
        ->keyBy(
            fn ($detail) =>
                $detail->client_id.':'.
                ($detail->site_id ?? 'main')
        );

        return $user->locationAssignments()
            ->with(['company', 'site'])
            ->get()
            ->filter(function ($assignment) {
                if (! $assignment->company?->is_active) {
                    return false;
                }

                return $assignment->site_id === null
                    || (
                        $assignment->site?->is_active
                        && (int) $assignment->site->client_id
                            === (int) $assignment->client_id
                    );
            })
            ->map(function ($assignment) use ($workDetails) {
                $key = $assignment->client_id.':'.
                    ($assignment->site_id ?? 'main');

                $detail = $workDetails->get($key);

                return [
                    'id' => $assignment->id,
                    'client_id' => $assignment->client_id,
                    'site_id' => $assignment->site_id,
                    'label' => self::label(
                        $assignment->company,
                        $assignment->site,
                    ),
                    'job_role' => $detail?->job_role,
                    'salary' => $detail?->salary,
                    'travel_allowance' => $detail?->travel_allowance,
                    'travel_allowance_currency' =>
                        $detail?->travel_allowance_currency,
                    'start_shift' => $detail?->start_shift,
                    'end_shift' => $detail?->end_shift,
                ];
            })
            ->sortBy('label')
            ->values();
    }

    // Call inside a database transaction.
    public function resolve(
        User $user,
        int $assignmentId,
    ): UserLocationAssignment {
        $assignment = $user->locationAssignments()->find($assignmentId);

        if (! $assignment) {
            $this->invalid();
        }

        $company = Client::query()
            ->whereKey($assignment->client_id)
            ->lockForUpdate()
            ->first();

        if (! $company || ! $company->is_active) {
            $this->invalid();
        }

        if ($assignment->site_id !== null) {
            $site = Site::query()
                ->whereKey($assignment->site_id)
                ->lockForUpdate()
                ->first();

            if (
                ! $site ||
                ! $site->is_active ||
                (int) $site->client_id !== (int) $company->id
            ) {
                $this->invalid();
            }
        }

        // Recheck after locking in case the assignment was removed.
        $assignment = $user->locationAssignments()
            ->whereKey($assignmentId)
            ->lockForUpdate()
            ->first();

        if (! $assignment) {
            $this->invalid();
        }

        return $assignment;
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages([
            'location_assignment_id' =>
                'Select an active location assigned to this user.',
        ]);
    }
}
