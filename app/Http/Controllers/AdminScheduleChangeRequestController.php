<?php

namespace App\Http\Controllers;

use App\Models\DailySchedule;
use App\Models\ScheduleChangeRequest;
use App\Notifications\ScheduleChangeApproved;
use App\Notifications\ScheduleChangeRejected;
use App\Services\AttendanceRecalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminScheduleChangeRequestController extends Controller
{
    public function __construct(private AttendanceRecalculationService $attendanceRecalculation) {}

    public function approve(Request $request, ScheduleChangeRequest $scheduleChangeRequest): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('schedules'), 403);

        $request->merge([
            'approved_start_time' => $request->approved_start_time ? substr($request->approved_start_time, 0, 5) : null,
            'approved_end_time'   => $request->approved_end_time   ? substr($request->approved_end_time, 0, 5)   : null,
        ]);

        $validated = $request->validate([
            'is_day_off'          => 'boolean',
            'approved_start_time' => 'nullable|date_format:H:i',
            'approved_end_time'   => 'nullable|date_format:H:i',
        ]);

        $isDayOff = (bool) ($validated['is_day_off'] ?? false);

        if (!$isDayOff && (empty($validated['approved_start_time']) || empty($validated['approved_end_time']))) {
            return back()->withErrors(['approved_start_time_' . $scheduleChangeRequest->id => 'Please enter both start and end times.']);
        }

        $daily = DailySchedule::updateOrCreate(
            [
                'employee_id' => $scheduleChangeRequest->employee_id,
                'date'        => $scheduleChangeRequest->date->toDateString(),
            ],
            [
                'work_start_time' => $isDayOff ? null : $validated['approved_start_time'],
                'work_end_time'   => $isDayOff ? null : $validated['approved_end_time'],
                'is_day_off'      => $isDayOff,
            ]
        );

        $scheduleChangeRequest->update([
            'status'              => 'approved',
            'reviewed_by'         => Auth::id(),
            'reviewed_at'         => now(),
            'approved_start_time' => $isDayOff ? null : $validated['approved_start_time'],
            'approved_end_time'   => $isDayOff ? null : $validated['approved_end_time'],
            'daily_schedule_id'   => $daily->id,
        ]);

        $this->attendanceRecalculation->recomputeDtrAndRefreshGamification(
            $scheduleChangeRequest->employee,
            $scheduleChangeRequest->date->toDateString(),
        );

        $staffUser = $scheduleChangeRequest->employee->user;
        if ($staffUser) {
            $staffUser->notify(new ScheduleChangeApproved($scheduleChangeRequest, Auth::user()->name));
        }

        $name = $scheduleChangeRequest->employee->full_name;
        $date = $scheduleChangeRequest->date->format('M d, Y');

        return back()->with('success', "Schedule change for {$name} on {$date} approved.");
    }

    public function reject(Request $request, ScheduleChangeRequest $scheduleChangeRequest): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('schedules'), 403);

        $request->validate(['reason' => 'nullable|string|max:500']);

        $scheduleChangeRequest->update([
            'status'           => 'rejected',
            'reviewed_by'      => Auth::id(),
            'reviewed_at'      => now(),
            'rejection_reason' => $request->reason,
        ]);

        $staffUser = $scheduleChangeRequest->employee->user;
        if ($staffUser) {
            $staffUser->notify(new ScheduleChangeRejected($scheduleChangeRequest, Auth::user()->name));
        }

        $name = $scheduleChangeRequest->employee->full_name;
        $date = $scheduleChangeRequest->date->format('M d, Y');

        return back()->with('success', "Schedule change for {$name} on {$date} rejected.");
    }
}
