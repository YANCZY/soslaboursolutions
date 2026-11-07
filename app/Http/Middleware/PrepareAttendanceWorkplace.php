<?php

namespace App\Http\Middleware;

use App\Models\Attendance;
use App\Services\WorkplaceCoordinates;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class PrepareAttendanceWorkplace
{
    public function __construct(
        private readonly WorkplaceCoordinates $coordinates,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $isCheckIn = $request->routeIs('attendance.check-in');

        $attendance = Attendance::query()
            ->with(['client', 'site'])
            ->where('employee_id', $request->user()->id)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->latest('check_in_date')
            ->latest('check_in_time')
            ->first();

        // Preserve the controller's existing "already checked in" response.
        if ($isCheckIn && $attendance) {
            return $next($request);
        }

        if (! $isCheckIn && ! $attendance) {
            $this->reject('You must be checked in first.');
        }

        $rules = [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'captured_at' => ['required', 'integer', 'min:1'],
        ];

        if ($isCheckIn) {
            $rules['location_assignment_id'] = ['required', 'integer'];
        }

        $validated = $request->validate($rules);

        if ($isCheckIn) {
            // Only allow preparation of a workplace assigned to this user.
            $assignment = $request->user()
                ->locationAssignments()
                ->with(['company', 'site'])
                ->find($validated['location_assignment_id']);

            if (! $assignment) {
                $this->reject(
                    'Select an active workplace assigned to your account.'
                );
            }

            $company = $assignment->company;
            $siteId = $assignment->site_id;
            $site = $assignment->site;
        } else {
            // Lunch uses the workplace recorded when the shift started.
            $company = $attendance->client;
            $siteId = $attendance->site_id;
            $site = $attendance->site;
        }

        if (! $company || ($isCheckIn && ! $company->is_active)) {
            $this->reject('The workplace company is unavailable.');
        }

        if ($siteId !== null) {
            if (
                ! $site
                || (int) $site->client_id !== (int) $company->id
                || ($isCheckIn && ! $site->is_active)
            ) {
                $this->reject('The workplace site is unavailable.');
            }

            $target = $site;
        } else {
            $target = $company;
        }

        if ($target->geofence_pinned_at === null) {
            $this->reject(
                'An administrator must pin this workplace location before attendance can be recorded.'
            );
        }

        try {
            // Runs before the attendance-write transaction.
            $this->coordinates->resolve($target);
        } catch (ConnectionException | RequestException | RuntimeException $exception) {
            Log::warning(
                'Attendance workplace preparation failed.',
                [
                    'workplace_type' => $target->getTable(),
                    'workplace_id' => $target->getKey(),
                    'exception_type' => get_class($exception),
                    'http_status' => $exception instanceof RequestException
                        ? $exception->response->status()
                        : null,
                ],
            );

            $this->reject(
                'The workplace location could not be prepared. '
                . 'Please try again or contact management.'
            );
        }

        return $next($request);
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'geolocation' => $message,
        ]);
    }
}
