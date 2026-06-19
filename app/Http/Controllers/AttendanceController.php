<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $todayAttendance = $this->latestOpenAttendance($request);

        $weekStart = now($this->userTimezone($request))->startOfWeek(Carbon::SUNDAY)->toDateString();
        $weekEnd = now($this->userTimezone($request))->endOfWeek(Carbon::SATURDAY)->toDateString();

        $weeklyAttendance = Attendance::query()
            ->with([
                'user:id,first_name,last_name',
                'user.profile:id,user_id,start_shift,end_shift',
            ])
            ->where('employee_id', $user->id)
            ->whereBetween('check_in_date', [$weekStart, $weekEnd])
            ->orderByDesc('check_in_date')
            ->orderByDesc('check_in_time')
            ->get();


        return Inertia::render('attendance/index', [
            'todayAttendance' => $todayAttendance,
            'weeklyAttendance' => $weeklyAttendance,
        ]);

    }

    public function checkIn(Request $request): JsonResponse
{
    $now = now($this->userTimezone($request));

    $activeAttendance = $this->latestOpenAttendance($request);

    if ($activeAttendance) {
        return response()->json([
            'message' => 'Already checked in.',
            'attendance' => $activeAttendance,
        ]);
    }

    $attendance = Attendance::query()->create([
        'employee_id' => $request->user()->id,
        'check_in_date' => $now->toDateString(),
        'status' => 'checked_in',
        'check_in_time' => $now->format('H:i:s'),
    ]);

    return response()->json([
        'message' => 'Checked in successfully.',
        'attendance' => $attendance->fresh(),
    ]);
}

    public function startLunch(Request $request): JsonResponse
    {
        $now = now($this->userTimezone($request));
        $attendance = $this->todayAttendance($request);

        if (! $attendance || $attendance->check_out_time) {
            abort(422, 'You must be checked in before starting lunch.');
        }

        if ($attendance->lunch_start_time && ! $attendance->lunch_end_time) {
            abort(422, 'Lunch break already started.');
        }

        $attendance->update([
            'status' => 'lunch_break',
            'lunch_start_time' => $now->format('H:i:s'),
        ]);

        return response()->json(['attendance' => $attendance->fresh()]);
    }

    public function endLunch(Request $request): JsonResponse
    {
        $now = now($this->userTimezone($request));
        $attendance = $this->todayAttendance($request);

        if (! $attendance || ! $attendance->lunch_start_time) {
            abort(422, 'Lunch break has not started.');
        }

        if ($attendance->lunch_end_time) {
            abort(422, 'Lunch break already ended.');
        }

        $attendance->update([
            'status' => 'checked_in',
            'lunch_end_time' => $now->format('H:i:s'),
        ]);

        return response()->json(['attendance' => $attendance->fresh()]);
    }

    public function checkOut(Request $request): JsonResponse
    {
       $attendance = $this->latestOpenAttendance($request);

        if (! $attendance || ! $attendance->check_in_time) {
            abort(422, 'You must check in first.');
        }

        if ($attendance->lunch_start_time && ! $attendance->lunch_end_time) {
            abort(422, 'End lunch break before checking out.');
        }

        if ($attendance->check_out_time) {
            abort(422, 'You already checked out.');
        }

        $checkIn = Carbon::parse($attendance->check_in_date->format('Y-m-d').' '.$attendance->check_in_time);
        $checkOut = now($this->userTimezone($request));

        $lunchSeconds = 0;

        if ($attendance->lunch_start_time && $attendance->lunch_end_time) {
            $lunchStart = Carbon::parse($attendance->check_in_date->format('Y-m-d').' '.$attendance->lunch_start_time);
            $lunchEnd = Carbon::parse($attendance->check_in_date->format('Y-m-d').' '.$attendance->lunch_end_time);

            $lunchSeconds = (int) floor($lunchEnd->diffInSeconds($lunchStart, true));
        }

        $totalWorkingSeconds = (int) floor(
            max(0, $checkOut->diffInSeconds($checkIn, true) - $lunchSeconds)
        );


        // Max Working hours
        $maxWorkingSeconds = $this->maxWorkingSeconds();

        if ($totalWorkingSeconds > $maxWorkingSeconds) {
            return response()->json([
                'requires_forgot_logout_modal' => true,
                'message' => 'You forgot to logout.',
                'attendance' => [
                    'id' => $attendance->id,
                    'check_in_date' => $attendance->check_in_date?->format('Y-m-d'),
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => null,
                ],
            ], 422);
        }

        $attendance->update([
            'status' => 'checked_out',
            'check_out_time' => $checkOut->format('H:i:s'),
            'total_working_seconds' => $totalWorkingSeconds,
        ]);

        return response()->json(['attendance' => $attendance->fresh()]);
    }

    private function maxWorkingSeconds(): int
    {
        // Actual Hours
        $maxWorkingSeconds = 19 * 60 * 60;

        //  TESTING MINUTES
        // $maxWorkingSeconds = 1 * 60;
        return $maxWorkingSeconds;
    }

    public function storeForgotCheckOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attendance_id' => ['required', 'integer', 'exists:attendance,id'],
            'check_out_time' => ['required', 'date_format:H:i'],
        ]);

        $attendance = Attendance::query()
            ->where('id', $validated['attendance_id'])
            ->where('employee_id', $request->user()->id)
            ->firstOrFail();

        if (! $attendance->check_in_time) {
            abort(422, 'Check-in time is missing.');
        }

        $checkIn = Carbon::parse(
            $attendance->check_in_date->format('Y-m-d') . ' ' . $attendance->check_in_time
        );

        $manualCheckOut = Carbon::parse(
            $attendance->check_in_date->format('Y-m-d') . ' ' . $validated['check_out_time']
        );

        if ($manualCheckOut->lessThanOrEqualTo($checkIn)) {
            abort(422, 'Check-out time must be later than check-in time.');
        }

        $lunchSeconds = 0;

        if ($attendance->lunch_start_time && $attendance->lunch_end_time) {
            $lunchStart = Carbon::parse($attendance->check_in_date->format('Y-m-d') . ' ' . $attendance->lunch_start_time);
            $lunchEnd = Carbon::parse($attendance->check_in_date->format('Y-m-d') . ' ' . $attendance->lunch_end_time);
            $lunchSeconds = (int) floor($lunchEnd->diffInSeconds($lunchStart, true));
        }

        $totalWorkingSeconds = (int) floor(
            max(0, $manualCheckOut->diffInSeconds($checkIn, true) - $lunchSeconds)
        );

        $maxWorkingSeconds = $this->maxWorkingSeconds();

        if ($totalWorkingSeconds > $maxWorkingSeconds) {
            abort(422, 'Check-out time exceeds the maximum allowed working hours.');
        }

        $attendance->update([
            'status' => 'checked_out',
            'check_out_time' => $manualCheckOut->format('H:i:s'),
            'total_working_seconds' => $totalWorkingSeconds,
        ]);

        return response()->json([
            'message' => 'Attendance updated successfully.',
            'attendance' => $attendance->fresh(),
        ]);
    }

    public function updateAttendance(Request $request, Attendance $attendance): JsonResponse
    {
        abort_unless($attendance->employee_id === $request->user()->id, 403);

        $validated = $request->validate([
            'check_in_date' => ['required', 'date'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'lunch_start_time' => ['nullable', 'date_format:H:i'],
            'lunch_end_time' => ['nullable', 'date_format:H:i'],
        ]);

        if (($validated['lunch_start_time'] && ! $validated['lunch_end_time']) ||
            (! $validated['lunch_start_time'] && $validated['lunch_end_time'])) {
            abort(422, 'Lunch start and lunch end must both be provided.');
        }

        $checkIn = Carbon::parse($validated['check_in_date'].' '.$validated['check_in_time']);
        $checkOut = Carbon::parse($validated['check_in_date'].' '.$validated['check_out_time']);

        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            abort(422, 'Check-out time must be later than check-in time.');
        }

        $lunchSeconds = 0;

        if ($validated['lunch_start_time'] && $validated['lunch_end_time']) {
            $lunchStart = Carbon::parse($validated['check_in_date'].' '.$validated['lunch_start_time']);
            $lunchEnd = Carbon::parse($validated['check_in_date'].' '.$validated['lunch_end_time']);

            if ($lunchEnd->lessThanOrEqualTo($lunchStart)) {
                abort(422, 'Lunch end time must be later than lunch start time.');
            }

            $lunchSeconds = (int) floor($lunchEnd->diffInSeconds($lunchStart, true));
        }

        $attendance->update([
            'check_in_date' => $validated['check_in_date'],
            'check_in_time' => $validated['check_in_time'],
            'check_out_time' => $validated['check_out_time'],
            'lunch_start_time' => $validated['lunch_start_time'],
            'lunch_end_time' => $validated['lunch_end_time'],
            'status' => 'checked_out',
            'total_working_seconds' => (int) floor(max(0, $checkOut->diffInSeconds($checkIn, true) - $lunchSeconds)),
        ]);

        return response()->json([
            'message' => 'Attendance updated successfully.',
            'attendance' => $attendance->fresh('user:id,first_name,last_name'),
        ]);
    }

    public function destroyAttendance(Request $request, Attendance $attendance): JsonResponse
    {
        abort_unless($attendance->employee_id === $request->user()->id, 403);

        $attendance->delete();

        return response()->json([
            'message' => 'Attendance deleted successfully.',
        ]);
    }


    private function userTimezone(Request $request): string
    {
        $timezone = $request->header('X-Timezone');

        return in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : config('app.timezone');
    }

    private function todayAttendance(Request $request): ?Attendance
    {
        return $this->latestOpenAttendance($request);
    }

    private function latestOpenAttendance(Request $request): ?Attendance
    {
        return Attendance::query()
            ->where('employee_id', $request->user()->id)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->latest('check_in_date')
            ->latest('check_in_time')
            ->first();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
