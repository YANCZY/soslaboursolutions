<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Inertia\Inertia;
use Inertia\Response;

class ForApprovalController extends Controller
{
    public function index(): Response
    {
        $approvalRecords = Attendance::query()
            ->with([
                'user:id,first_name,last_name',
                'client:id,company_name',
            ])
            ->where('approval_status', 'pending')
            ->orderByDesc('submitted_for_approval_at')
            ->orderByDesc('check_in_date')
            ->get()
            ->map(function (Attendance $attendance) {
                return [
                    'id' => $attendance->id,
                    'date' => $attendance->check_in_date?->format('M d, Y'),
                    'name' => trim(($attendance->user?->first_name ?? '') . ' ' . ($attendance->user?->last_name ?? '')),
                    'company' => $attendance->client?->company_name ?? '-',
                    'check_in' => $attendance->check_in_time,
                    'check_out' => $attendance->check_out_time,
                    'total_work_hours' => $attendance->total_working_seconds,
                    'total_overtime' => 0,
                ];
            });

        return Inertia::render('admin/ForApprovals', [
            'approvalRecords' => $approvalRecords,
        ]);
    }
}
