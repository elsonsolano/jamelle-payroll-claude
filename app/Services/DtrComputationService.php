<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Carbon\Carbon;

class DtrComputationService
{
    /**
     * Compute derived DTR fields from raw time inputs.
     *
     * Returns an array with: total_hours, overtime_hours, late_mins, undertime_mins, is_rest_day
     */
    public function compute(Employee $employee, string $date, ?string $timeIn, ?string $amOut, ?string $pmIn, ?string $timeOut, ?float $otHours = null): array
    {
        $carbon = Carbon::parse($date);
        $dayName = $carbon->format('l'); // e.g., "Monday"

        // Resolve schedule for this date
        $schedule = $employee->employeeSchedules()
            ->where('week_start_date', '<=', $date)
            ->orderByDesc('week_start_date')
            ->first();

        $restDays = $schedule?->rest_days ?? ['Sunday'];
        $isRestDay = in_array($dayName, $restDays);

        $workStart = $schedule?->work_start_time;
        $workEnd   = $schedule?->work_end_time;

        // Compute total_hours: time_in → time_out minus break (am_out → pm_in)
        $totalHours = 0;
        if ($timeIn && $timeOut) {
            $in    = Carbon::createFromTimeString($timeIn);
            $out   = Carbon::createFromTimeString($timeOut);
            $total = $in->diffInMinutes($out);

            if ($amOut && $pmIn) {
                $breakStart = Carbon::createFromTimeString($amOut);
                $breakEnd   = Carbon::createFromTimeString($pmIn);
                $breakMins  = $breakStart->diffInMinutes($breakEnd);
                $total      = max(0, $total - $breakMins);
            }

            $totalHours = round($total / 60, 2);
        }

        // overtime_hours provided directly by staff input
        $overtimeHours = $otHours !== null ? round(max(0, $otHours), 2) : 0;

        // Late mins: how many minutes after work_start did they clock in
        $lateMins = 0;
        if ($timeIn && !$isRestDay && $workStart) {
            $scheduledIn = Carbon::createFromTimeString($workStart);
            $actualIn    = Carbon::createFromTimeString($timeIn);
            if ($actualIn->gt($scheduledIn)) {
                $lateMins = (int) $scheduledIn->diffInMinutes($actualIn);
            }
        }

        // Undertime mins: how many minutes before work_end did they clock out
        $undertimeMins = 0;
        if ($timeOut && !$isRestDay && $workEnd) {
            $scheduledOut = Carbon::createFromTimeString($workEnd);
            $actualOut    = Carbon::createFromTimeString($timeOut);
            if ($actualOut->lt($scheduledOut)) {
                $undertimeMins = (int) $actualOut->diffInMinutes($scheduledOut);
            }
        }

        return [
            'total_hours'    => $totalHours,
            'overtime_hours' => $overtimeHours,
            'late_mins'      => $lateMins,
            'undertime_mins' => $undertimeMins,
            'is_rest_day'    => $isRestDay,
        ];
    }

    /**
     * Resolve which users should approve this employee's OT request.
     */
    public static function getOtApprovers(Employee $employee, \App\Models\User $submitter): \Illuminate\Database\Eloquent\Collection
    {
        $branch = $employee->branch;
        $isHeadOffice = strtolower(trim($branch->name)) === 'head office';

        if ($submitter->can_approve_ot && !$isHeadOffice) {
            // Branch approver → escalate to Head Office approvers
            $headOffice = Branch::whereRaw('LOWER(TRIM(name)) = ?', ['head office'])->first();
            if (!$headOffice) {
                return \App\Models\User::where('role', 'admin')->get();
            }
            return \App\Models\User::where('can_approve_ot', true)
                ->whereHas('employee', fn($q) => $q->where('branch_id', $headOffice->id))
                ->get();
        }

        if ($submitter->can_approve_ot && $isHeadOffice) {
            // Head Office approver → escalate to admin
            return \App\Models\User::where('role', 'admin')->get();
        }

        // Regular staff → branch approvers
        return \App\Models\User::where('can_approve_ot', true)
            ->whereHas('employee', fn($q) => $q->where('branch_id', $branch->id))
            ->get();
    }
}
