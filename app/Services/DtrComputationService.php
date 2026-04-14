<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\DailySchedule;
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

        // Check date-specific DailySchedule first (takes priority over weekly schedule)
        $dailySchedule = DailySchedule::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        if ($dailySchedule) {
            $isRestDay = $dailySchedule->is_day_off;
            $workStart = $dailySchedule->work_start_time;
            $workEnd   = $dailySchedule->work_end_time;
        } else {
            // Fall back to weekly EmployeeSchedule
            $schedule = $employee->employeeSchedules()
                ->where('week_start_date', '<=', $date)
                ->orderByDesc('week_start_date')
                ->first();

            $restDays  = $schedule?->rest_days ?? [];
            $isRestDay = $schedule !== null && in_array($dayName, $restDays);
            $workStart = $schedule?->work_start_time;
            $workEnd   = $schedule?->work_end_time;
        }

        // Late mins: how many minutes after work_start did they clock in.
        // Computed first because it caps total_hours below.
        $lateMins = 0;
        if ($timeIn && !$isRestDay && $workStart) {
            $scheduledIn = Carbon::createFromTimeString($workStart);
            $actualIn    = Carbon::createFromTimeString($timeIn);
            if ($actualIn->gt($scheduledIn)) {
                $lateMins = (int) $scheduledIn->diffInMinutes($actualIn);
            }
        }

        // Compute total_hours using schedule-aware boundaries:
        //   effective start = max(actual time_in,  scheduled_start)  → early arrival ignored
        //   effective end   = min(actual time_out, scheduled_end)    → staying late ignored
        // When no schedule exists, actual times are used as-is.
        $totalHours = 0;
        if ($timeIn && $timeOut) {
            $in  = Carbon::createFromTimeString($timeIn);
            $out = Carbon::createFromTimeString($timeOut);

            // Handle overnight shifts: actual time_out past midnight.
            if ($out->lte($in)) {
                $out->addDay();
            }

            $effectiveIn  = $in->copy();
            $effectiveOut = $out->copy();

            if (!$isRestDay && $workStart && $workEnd) {
                $schedStart = Carbon::createFromTimeString($workStart);
                $schedEnd   = Carbon::createFromTimeString($workEnd);

                // Overnight scheduled shift (e.g. 21:00 → 06:00).
                if ($schedEnd->lte($schedStart)) {
                    $schedEnd->addDay();
                }
                // Edge case: schedule window is behind the actual in-time (rare overnight edge).
                if ($schedEnd->lte($in)) {
                    $schedStart->addDay();
                    $schedEnd->addDay();
                }

                // Early arrival: don't credit hours before the scheduled start.
                if ($effectiveIn->lt($schedStart)) {
                    $effectiveIn = $schedStart->copy();
                }

                // Staying late: don't credit regular hours past the scheduled end.
                // (Extra time requires an approved OT entry.)
                if ($effectiveOut->gt($schedEnd)) {
                    $effectiveOut = $schedEnd->copy();
                }
            }

            $total = max(0, $effectiveIn->diffInMinutes($effectiveOut));

            if ($amOut && $pmIn) {
                $breakStart = Carbon::createFromTimeString($amOut);
                $breakEnd   = Carbon::createFromTimeString($pmIn);
                if ($breakEnd->lte($breakStart)) {
                    $breakEnd->addDay();
                }
                $breakMins = $breakStart->diffInMinutes($breakEnd);
                $total     = max(0, $total - $breakMins);
            }

            $totalHours = round($total / 60, 2);
        }

        // overtime_hours provided directly by staff input
        $overtimeHours = $otHours !== null ? round(max(0, $otHours), 2) : 0;

        // Undertime mins: how many minutes before work_end did they clock out
        $undertimeMins = 0;
        if ($timeOut && !$isRestDay && $workEnd) {
            $scheduledOut = Carbon::createFromTimeString($workEnd);
            $actualOut    = Carbon::createFromTimeString($timeOut);
            // If scheduled end is past midnight relative to time_in, adjust both
            if ($timeIn) {
                $shiftStart = Carbon::createFromTimeString($timeIn);
                if ($scheduledOut->lte($shiftStart)) {
                    $scheduledOut->addDay();
                }
                if ($actualOut->lte($shiftStart)) {
                    $actualOut->addDay();
                }
            }
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
            // Branch approver → notify same-branch peers AND Head Office approvers
            $headOffice = Branch::whereRaw('LOWER(TRIM(name)) = ?', ['head office'])->first();

            $peers = \App\Models\User::where('can_approve_ot', true)
                ->where('id', '!=', $submitter->id)
                ->whereHas('employee', fn($q) => $q->where('branch_id', $branch->id))
                ->get();

            $hoApprovers = $headOffice
                ? \App\Models\User::where('can_approve_ot', true)
                    ->whereHas('employee', fn($q) => $q->where('branch_id', $headOffice->id))
                    ->get()
                : \App\Models\User::where('role', 'admin')->get();

            return $peers->merge($hoApprovers);
        }

        if ($submitter->can_approve_ot && $isHeadOffice) {
            // Head Office approver → escalate to admin
            return \App\Models\User::where('role', 'admin')->get();
        }

        // Regular staff → branch approvers + HO approvers
        $headOffice = Branch::whereRaw('LOWER(TRIM(name)) = ?', ['head office'])->first();

        $branchApprovers = \App\Models\User::where('can_approve_ot', true)
            ->whereHas('employee', fn($q) => $q->where('branch_id', $branch->id))
            ->get();

        $hoApprovers = $headOffice
            ? \App\Models\User::where('can_approve_ot', true)
                ->whereHas('employee', fn($q) => $q->where('branch_id', $headOffice->id))
                ->where('id', '!=', $submitter->id)
                ->get()
            : collect();

        return $branchApprovers->merge($hoApprovers)->unique('id');
    }
}
