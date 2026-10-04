<?php

namespace App\Http\Middleware;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\TravelAllowance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyIsActive
{
    /**
     * Keep company checks and the corresponding write in one transaction.
     * Company deactivation uses the same row locks.
     */
    public function handle(Request $request, Closure $next, string $mode): Response
    {
        return DB::transaction(function () use ($request, $next, $mode) {
            $references = $this->references($request, $mode);
            $companies = Client::query()
                ->whereIn('id', array_keys($references))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($references as $id => $reference) {
                $company = $companies->get($id);

                if (! $company || ($reference['active'] && ! $company->is_active)) {
                    throw ValidationException::withMessages([
                        $reference['field'] => 'This company is inactive or unavailable.',
                    ]);
                }
            }

            return $next($request);
        });
    }

    private function references(Request $request, string $mode): array
    {
        $references = [];
        $add = function ($id, string $field = 'client_id', bool $active = true) use (&$references): void {
            if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id <= 0) {
                return; // Existing request validation handles malformed input.
            }

            $id = (int) $id;
            $previous = $references[$id] ?? null;
            $references[$id] = [
                'field' => $active ? $field : ($previous['field'] ?? $field),
                'active' => $active || ($previous['active'] ?? false),
            ];
        };

        if ($mode === 'assignments') {
            $user = $request->route('user');
            $existing = $user?->clients()->pluck('clients.id')->map(fn ($id) => (int) $id)->all() ?? [];

            if (is_array($request->input('client_ids'))) {
                foreach ($request->input('client_ids') as $index => $id) {
                    $add($id, "client_ids.{$index}", ! in_array((int) $id, $existing, true));
                }
            }

            $add($request->input('work_detail.client_id'), 'work_detail.client_id');
        } elseif ($mode === 'employee') {
            $id = $request->user()->client_id;
            Client::assertActiveForUse($id);
            $add($id);
        } else {
            $add($request->input('client_id'));
        }

        if ($mode === 'attendance') {
            $record = $request->route('attendance');

            if (! $record && $request->routeIs('attendance.forgot-check-out')) {
                $record = Attendance::query()
                    ->where('employee_id', $request->user()->id)
                    ->find($request->input('attendance_id'));
            }

            if (! $record && $request->routeIs(
                'attendance.check-in', 'attendance.lunch.*', 'attendance.check-out',
            )) {
                $record = Attendance::query()
                    ->where('employee_id', $request->user()->id)
                    ->whereNotNull('check_in_time')
                    ->whereNull('check_out_time')
                    ->latest('check_in_date')
                    ->latest('check_in_time')
                    ->first();
            }

            if ($record) {
                $this->addRecord($record->client_id, $add);
            }

            if ($request->routeIs('attendance.submit-for-approval') && is_array($request->input('attendance_ids'))) {
                foreach (Attendance::query()
                    ->where('employee_id', $request->user()->id)
                    ->whereIn('id', $request->input('attendance_ids'))
                    ->get(['client_id']) as $record) {
                    $this->addRecord($record->client_id, $add);
                }
            }
        }

        if ($mode === 'travel') {
            if ($record = $request->route('travelAllowance')) {
                $this->addRecord($record->client_id, $add);
            }

            if ($request->routeIs('travel-allowance.submit-for-approval') && is_array($request->input('travel_allowance_ids'))) {
                foreach (TravelAllowance::query()
                    ->where('user_id', $request->user()->id)
                    ->whereIn('id', $request->input('travel_allowance_ids'))
                    ->get(['client_id']) as $record) {
                    $this->addRecord($record->client_id, $add);
                }
            }
        }

        return $references;
    }

    private function addRecord(?int $companyId, Closure $add): void
    {
        if (! $companyId) {
            throw ValidationException::withMessages([
                'client_id' => 'This record has no available company.',
            ]);
        }

        $add($companyId);
    }
}
