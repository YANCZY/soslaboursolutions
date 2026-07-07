<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ForApprovalController extends Controller
{
    public function index(Request $request): Response
{
    $status = $request->query('status', 'pending');

    if (! in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $status = 'pending';
    }
        $approvalRecords = Attendance::query()
            ->with([
                'user:id,first_name,last_name',
                'client:id,company_name',
            ])
            ->where('approval_status', $status)
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
                    'approval_status' => $attendance->approval_status,
                ];
            });

        return Inertia::render('admin/ForApprovals', [
            'approvalRecords' => $approvalRecords,
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    public function approve(Attendance $attendance): RedirectResponse
    {
        abort_unless(
            $attendance->approval_status === 'pending',
            422,
            'Only pending attendance records can be approved.'
        );

        $attendance->update([
            'approval_status' => 'approved',
        ]);

        return back();
    }

    public function reject(Attendance $attendance): RedirectResponse
    {
        abort_unless(
            $attendance->approval_status === 'pending',
            422,
            'Only pending attendance records can be rejected.'
        );

        $attendance->update([
            'approval_status' => 'rejected',
        ]);

        return back();
    }


}
