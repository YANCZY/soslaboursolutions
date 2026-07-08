<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\TravelAllowance;

class ForApprovalController extends Controller
{
    public function index(): Response
    {
        $attendanceApprovalRecords = Attendance::query()
            ->with([
                'user:id,first_name,last_name',
                'client:id,company_name',
            ])
            ->whereNotNull('approval_status')
            ->orderByDesc('submitted_for_approval_at')
            ->orderByDesc('check_in_date')
            ->get()
            ->map(function (Attendance $attendance) {
                return [
                    'id' => $attendance->id,
                    'date' => $attendance->check_in_date?->format('Y-m-d'),
                    'name' => trim(($attendance->user?->first_name ?? '') . ' ' . ($attendance->user?->last_name ?? '')),
                    'company' => $attendance->client?->company_name ?? '-',
                    'check_in' => $attendance->check_in_time,
                    'check_out' => $attendance->check_out_time,
                    'total_work_hours' => $attendance->total_working_seconds,
                    'approval_status' => $attendance->approval_status,
                    'total_overtime' => 0,
                ];
            });

            $travelAllowanceApprovalRecords = TravelAllowance::query()
                ->with([
                    'user:id,first_name,last_name',
                    'client:id,company_name',
                ])
                ->whereNotNull('approval_status')
                ->orderByDesc('submitted_for_approval_at')
                ->orderByDesc('date')
                ->get()
                ->map(function (TravelAllowance $travelAllowance) {
                    return [
                        'id' => $travelAllowance->id,
                        'date' => $travelAllowance->date,
                        'name' => $travelAllowance->name,
                        'company' => $travelAllowance->client?->company_name ?? '-',
                        'description' => $travelAllowance->description ?? '-',
                        'rate' => (float) $travelAllowance->rate,
                        'quantity' => (int) $travelAllowance->quantity,
                        'approval_status' => $travelAllowance->approval_status,
                        'amount' => (float) $travelAllowance->amount,
                    ];
                });

        return Inertia::render('admin/ForApprovals', [
            'attendanceApprovalRecords' => $attendanceApprovalRecords,
            'travelAllowanceApprovalRecords' => $travelAllowanceApprovalRecords,
        ]);
    }
}
