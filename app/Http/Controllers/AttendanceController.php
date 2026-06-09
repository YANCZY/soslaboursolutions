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

        $today = now($this->userTimezone($request))->toDateString();

        $todayAttendance = Attendance::query()
            ->where('employee_id', '=', $user->id, 'and')
            ->where(function ($query) {
                $query->where('status', 'checked_in')
                    ->orWhere('status', 'lunch_break');
            })
            ->latest()
            ->first();

        $weekStart = now($this->userTimezone($request))->startOfWeek(Carbon::SUNDAY)->toDateString();
        $weekEnd = now($this->userTimezone($request))->endOfWeek(Carbon::SATURDAY)->toDateString();

        $weeklyAttendance = Attendance::query()
            ->with('user:id,first_name,last_name')
            ->where('employee_id', $user->id)
            ->whereBetween('check_in_date', [$weekStart, $weekEnd])
            ->orderBy('check_in_date')
            ->orderBy('check_in_time')
            ->get();


        return Inertia::render('attendance/index', [
            'todayAttendance' => $todayAttendance,
            'weeklyAttendance' => $weeklyAttendance,
        ]);

    }

    public function checkIn(Request $request): JsonResponse
{
    $now = now($this->userTimezone($request));

    $activeAttendance = Attendance::query()
        ->where('employee_id', '=', $request->user()->id, 'and')
        ->whereDate('check_in_date', '=', $now->toDateString(), 'and')
        ->where(function ($query) {
            $query->where('status', 'checked_in')
                ->orWhere('status', 'lunch_break');
        })
        ->latest()
        ->first();

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
        $attendance = $this->todayAttendance($request);

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

            $lunchSeconds = (int) floor($lunchStart->diffInSeconds($lunchEnd));
        }

        $totalWorkingSeconds = (int) floor(
            max(0, $checkIn->diffInSeconds($checkOut) - $lunchSeconds)
        );

        $attendance->update([
            'status' => 'checked_out',
            'check_out_time' => $checkOut->format('H:i:s'),
            'total_working_seconds' => $totalWorkingSeconds,
        ]);

        return response()->json(['attendance' => $attendance->fresh()]);
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
         return Attendance::query()
        ->where('employee_id', '=', $request->user()->id, 'and')
        ->whereDate('check_in_date', '=', now($this->userTimezone($request))->toDateString(), 'and')
        ->where(function ($query) {
            $query->where('status', 'checked_in')
                ->orWhere('status', 'lunch_break');
        })
        ->latest()
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
