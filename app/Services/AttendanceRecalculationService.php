<?php

namespace App\Services;

use App\Models\Dtr;
use App\Models\Employee;
use App\Models\PayrollCutoff;

class AttendanceRecalculationService
{
    public function __construct(
        private DtrComputationService $dtrComputationService,
        private AttendanceScoringService $attendanceScoringService,
        private AttendanceBadgeService $attendanceBadgeService,
    ) {}

    public function recomputeDtrAndRefreshGamification(Employee $employee, string $date): ?Dtr
    {
        $dtr = $this->recomputeDtr($employee, $date);

        $this->refreshFinalizedGamification($employee, $date);

        return $dtr;
    }

    public function recomputeDtr(Employee $employee, string $date): ?Dtr
    {
        $dtr = Dtr::where('employee_id', $employee->id)->whereDate('date', $date)->first();

        if (! $dtr) {
            return null;
        }

        $computed = $this->dtrComputationService->compute(
            $employee,
            $date,
            $dtr->time_in,
            $dtr->am_out,
            $dtr->pm_in,
            $dtr->time_out,
            $dtr->overtime_hours > 0 ? (float) $dtr->overtime_hours : null,
        );

        $dtr->update([
            'total_hours' => $computed['total_hours'],
            'late_mins' => $computed['late_mins'],
            'undertime_mins' => $computed['undertime_mins'],
            'is_rest_day' => $computed['is_rest_day'],
        ]);

        return $dtr->fresh();
    }

    public function refreshFinalizedGamificationForDtr(Dtr $dtr): void
    {
        $dtr->loadMissing('employee');

        if (! $dtr->employee || ! $dtr->date) {
            return;
        }

        $this->refreshFinalizedGamification($dtr->employee, $dtr->date->toDateString());
    }

    public function refreshFinalizedGamification(Employee $employee, string $date): void
    {
        $cutoffs = PayrollCutoff::where('status', 'finalized')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereHas('payrollEntries', fn ($query) => $query->where('employee_id', $employee->id))
            ->get();

        foreach ($cutoffs as $cutoff) {
            $this->attendanceScoringService->scoreEmployeeForCutoff($cutoff, $employee);
            $this->attendanceBadgeService->awardBadgesForEmployee($cutoff, $employee);
        }
    }
}
