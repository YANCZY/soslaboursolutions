<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\TravelAllowance;

class ForApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $clientIds = $request->user()
            ->clients()
            ->pluck('clients.id')
            ->push($request->user()->client_id)
            ->filter()
            ->unique()
            ->values();

        $attendanceApprovalRecords = Attendance::query()
            ->with([
                'user:id,first_name,last_name',
                'client:id,company_name',
            ])
            ->whereNotNull('approval_status')
            ->whereIn('client_id', $clientIds)
            ->orderByDesc('submitted_for_approval_at')
            ->orderByDesc('check_in_date')
            ->get()
            ->map(function (Attendance $attendance) {
                return [
                    'id' => $attendance->id,
                    'date' => $attendance->check_in_date?->format('Y-m-d'),
                    'name' => trim(($attendance->user?->first_name ?? '') . ' ' . ($attendance->user?->last_name ?? '')),
                    'covering_for' => $attendance->covering_for,
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
                ->whereIn('client_id', $clientIds)
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
