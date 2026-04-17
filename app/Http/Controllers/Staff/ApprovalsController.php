<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DailySchedule;
use App\Models\Dtr;
use App\Models\ScheduleChangeRequest;
use App\Notifications\OtApproved;
use App\Notifications\OtRejected;
use App\Notifications\ScheduleChangeApproved;
use App\Notifications\ScheduleChangeRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApprovalsController extends Controller
{
    // ─────────────────────────────────────────────
    // Index — tabbed view: OT + Schedule Changes
    // ─────────────────────────────────────────────

    public function index(): View
    {
        $pendingDtrs           = $this->approvableOtQuery()->with('employee.branch')->orderBy('date')->get();
        $pendingScheduleChanges = $this->approvableScheduleQuery()->with('employee.branch')->orderBy('date')->get();

        $activeTab = request('tab', $pendingDtrs->isEmpty() && $pendingScheduleChanges->isNotEmpty() ? 'schedule' : 'ot');

        return view('staff.approvals.index', compact('pendingDtrs', 'pendingScheduleChanges', 'activeTab'));
    }

    // ─────────────────────────────────────────────
    // OT approval actions
    // ─────────────────────────────────────────────

    public function approveOt(Dtr $dtr): RedirectResponse
    {
        $this->ensureCanApproveOt($dtr);

        $dtr->update([
            'ot_status'           => 'approved',
            'ot_approved_by'      => Auth::id(),
            'ot_approved_at'      => now(),
            'ot_rejection_reason' => null,
        ]);

        $staffUser = $dtr->employee->user;
        if ($staffUser) {
            $staffUser->notify(new OtApproved($dtr, Auth::user()->name));
        }

        return back()->with('success', "OT for {$dtr->employee->full_name} on {$dtr->date->format('M d, Y')} approved.");
    }

    public function rejectOt(Request $request, Dtr $dtr): RedirectResponse
    {
        $this->ensureCanApproveOt($dtr);

        $request->validate(['reason' => 'nullable|string|max:500']);

        $dtr->update([
            'ot_status'           => 'rejected',
            'ot_approved_by'      => Auth::id(),
            'ot_approved_at'      => now(),
            'ot_rejection_reason' => $request->reason,
            'overtime_hours'      => 0,
        ]);

        $staffUser = $dtr->employee->user;
        if ($staffUser) {
            $staffUser->notify(new OtRejected($dtr, Auth::user()->name));
        }

        return back()->with('success', "OT for {$dtr->employee->full_name} on {$dtr->date->format('M d, Y')} rejected.");
    }

    // ─────────────────────────────────────────────
    // Schedule change approval actions
    // ─────────────────────────────────────────────

    public function approveSchedule(Request $request, ScheduleChangeRequest $scheduleChangeRequest): RedirectResponse
    {
        $this->ensureCanApproveSchedule($scheduleChangeRequest);

        $validated = $request->validate([
            'is_day_off'          => 'boolean',
            'approved_start_time' => 'nullable|date_format:H:i',
            'approved_end_time'   => 'nullable|date_format:H:i',
        ]);

        $isDayOff = (bool) ($validated['is_day_off'] ?? false);

        if (!$isDayOff && (empty($validated['approved_start_time']) || empty($validated['approved_end_time']))) {
            return back()->withErrors(['approved_start_time' => 'Please enter both start and end times.']);
        }

        // Create or update the DailySchedule for this employee + date
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

        $staffUser = $scheduleChangeRequest->employee->user;
        if ($staffUser) {
            $staffUser->notify(new ScheduleChangeApproved($scheduleChangeRequest, Auth::user()->name));
        }

        $name = $scheduleChangeRequest->employee->full_name;
        $date = $scheduleChangeRequest->date->format('M d, Y');

        return back()->with('success', "Schedule change for {$name} on {$date} approved.");
    }

    public function rejectSchedule(Request $request, ScheduleChangeRequest $scheduleChangeRequest): RedirectResponse
    {
        $this->ensureCanApproveSchedule($scheduleChangeRequest);

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

    // ─────────────────────────────────────────────
    // Shared helpers
    // ─────────────────────────────────────────────

    private function approvableOtQuery()
    {
        $user         = Auth::user();
        $employee     = $user->employee;
        $branch       = $employee?->branch;
        $isHeadOffice = $branch && strtolower(trim($branch->name)) === 'head office';

        $query = Dtr::where('ot_status', 'pending');

        if ($user->isAdmin()) {
            $headOffice = Branch::whereRaw('LOWER(TRIM(name)) = ?', ['head office'])->first();
            $query->whereHas('employee', function ($q) use ($headOffice) {
                $q->where('branch_id', $headOffice?->id)
                  ->whereHas('user', fn($u) => $u->where('can_approve_ot', true));
            });
        } elseif ($user->can_approve_ot && $isHeadOffice) {
            // HO approver sees all pending OT
        } elseif ($user->can_approve_ot && !$isHeadOffice) {
            $query->whereHas('employee', function ($q) use ($branch, $employee) {
                $q->where('branch_id', $branch->id)
                  ->where('id', '!=', $employee->id);
            });
        } else {
            $query->whereRaw('0 = 1');
        }

        return $query;
    }

    private function approvableScheduleQuery()
    {
        $user         = Auth::user();
        $employee     = $user->employee;
        $branch       = $employee?->branch;
        $isHeadOffice = $branch && strtolower(trim($branch->name)) === 'head office';

        $query = ScheduleChangeRequest::where('status', 'pending');

        if ($user->isAdmin()) {
            $headOffice = Branch::whereRaw('LOWER(TRIM(name)) = ?', ['head office'])->first();
            $query->whereHas('employee', function ($q) use ($headOffice) {
                $q->where('branch_id', $headOffice?->id)
                  ->whereHas('user', fn($u) => $u->where('can_approve_ot', true));
            });
        } elseif ($user->can_approve_ot && $isHeadOffice) {
            // HO approver sees all pending schedule changes
        } elseif ($user->can_approve_ot && !$isHeadOffice) {
            $query->whereHas('employee', function ($q) use ($branch, $employee) {
                $q->where('branch_id', $branch->id)
                  ->where('id', '!=', $employee->id);
            });
        } else {
            $query->whereRaw('0 = 1');
        }

        return $query;
    }

    private function ensureCanApproveOt(Dtr $dtr): void
    {
        if (!$this->approvableOtQuery()->where('id', $dtr->id)->exists()) {
            abort(403, 'You are not authorized to approve this overtime request.');
        }
    }

    private function ensureCanApproveSchedule(ScheduleChangeRequest $changeRequest): void
    {
        if (!$this->approvableScheduleQuery()->where('id', $changeRequest->id)->exists()) {
            abort(403, 'You are not authorized to approve this schedule change request.');
        }
    }
}
