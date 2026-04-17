<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DailySchedule;
use App\Models\ScheduleChangeRequest;
use App\Notifications\ScheduleChangeRequested;
use App\Services\DtrComputationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleChangeRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date'                       => 'required|date|after_or_equal:today',
            'is_day_off'                 => 'boolean',
            'requested_work_start_time'  => 'nullable|date_format:H:i',
            'requested_work_end_time'    => 'nullable|date_format:H:i',
            'reason'                     => 'required|string|max:500',
        ]);

        $isDayOff = (bool) ($validated['is_day_off'] ?? false);

        if (!$isDayOff && (empty($validated['requested_work_start_time']) || empty($validated['requested_work_end_time']))) {
            return back()->withErrors(['requested_work_start_time' => 'Please enter both start and end times.']);
        }

        $user     = Auth::user();
        $employee = $user->employee;

        [$currentStart, $currentEnd, $isCurrentDayOff] = $this->currentScheduleSnapshot($employee, $validated['date']);

        // Upsert: if there's an existing pending/rejected request for this date, reset it
        $existing = ScheduleChangeRequest::where('employee_id', $employee->id)
            ->where('date', $validated['date'])
            ->whereIn('status', ['pending', 'rejected'])
            ->first();

        if ($existing) {
            $existing->update([
                'requested_work_start_time' => $isDayOff ? null : $validated['requested_work_start_time'],
                'requested_work_end_time'   => $isDayOff ? null : $validated['requested_work_end_time'],
                'is_day_off'                => $isDayOff,
                'reason'                    => $validated['reason'],
                'status'                    => 'pending',
                'reviewed_by'               => null,
                'reviewed_at'               => null,
                'rejection_reason'          => null,
                'approved_start_time'       => null,
                'approved_end_time'         => null,
            ]);
            $changeRequest = $existing;
        } else {
            $changeRequest = ScheduleChangeRequest::create([
                'employee_id'               => $employee->id,
                'date'                      => $validated['date'],
                'current_work_start_time'   => $currentStart,
                'current_work_end_time'     => $currentEnd,
                'is_current_day_off'        => $isCurrentDayOff,
                'requested_work_start_time' => $isDayOff ? null : $validated['requested_work_start_time'],
                'requested_work_end_time'   => $isDayOff ? null : $validated['requested_work_end_time'],
                'is_day_off'                => $isDayOff,
                'reason'                    => $validated['reason'],
                'status'                    => 'pending',
            ]);
        }

        // Notify approvers (same hierarchy as OT)
        $approvers = DtrComputationService::getOtApprovers($employee, $user);
        foreach ($approvers as $approver) {
            $approver->notify(new ScheduleChangeRequested($changeRequest));
        }

        return back()->with('success', 'Your schedule change request has been submitted.');
    }

    public function update(Request $request, ScheduleChangeRequest $scheduleChangeRequest): RedirectResponse
    {
        $this->authorizeOwner($scheduleChangeRequest);

        if ($scheduleChangeRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be edited.');
        }

        $validated = $request->validate([
            'is_day_off'                => 'boolean',
            'requested_work_start_time' => 'nullable|date_format:H:i',
            'requested_work_end_time'   => 'nullable|date_format:H:i',
            'reason'                    => 'required|string|max:500',
        ]);

        $isDayOff = (bool) ($validated['is_day_off'] ?? false);

        if (!$isDayOff && (empty($validated['requested_work_start_time']) || empty($validated['requested_work_end_time']))) {
            return back()->withErrors(['requested_work_start_time' => 'Please enter both start and end times.']);
        }

        $scheduleChangeRequest->update([
            'requested_work_start_time' => $isDayOff ? null : $validated['requested_work_start_time'],
            'requested_work_end_time'   => $isDayOff ? null : $validated['requested_work_end_time'],
            'is_day_off'                => $isDayOff,
            'reason'                    => $validated['reason'],
        ]);

        return back()->with('success', 'Your schedule change request has been updated.');
    }

    public function cancel(ScheduleChangeRequest $scheduleChangeRequest): RedirectResponse
    {
        $this->authorizeOwner($scheduleChangeRequest);

        if ($scheduleChangeRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be cancelled.');
        }

        $scheduleChangeRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Schedule change request cancelled.');
    }

    private function authorizeOwner(ScheduleChangeRequest $changeRequest): void
    {
        if ($changeRequest->employee_id !== Auth::user()->employee->id) {
            abort(403);
        }
    }

    /**
     * Resolve the staff member's current schedule for a given date (for snapshotting).
     */
    private function currentScheduleSnapshot(\App\Models\Employee $employee, string $date): array
    {
        $daily = DailySchedule::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        if ($daily) {
            return [$daily->work_start_time, $daily->work_end_time, $daily->is_day_off];
        }

        $weekly = $employee->employeeSchedules()
            ->where('week_start_date', '<=', $date)
            ->orderByDesc('week_start_date')
            ->first();

        if ($weekly) {
            $restDays  = $weekly->rest_days ?? ['Sunday'];
            $isRestDay = in_array(Carbon::parse($date)->format('l'), $restDays);
            return [
                $isRestDay ? null : $weekly->work_start_time,
                $isRestDay ? null : $weekly->work_end_time,
                $isRestDay,
            ];
        }

        return [null, null, false];
    }
}
