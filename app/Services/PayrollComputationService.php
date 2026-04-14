<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\PayrollCutoff;
use App\Models\PayrollDeduction;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryVariableDeduction;
use App\Services\DtrComputationService;

class PayrollComputationService
{
    public function computeEntry(PayrollCutoff $cutoff, Employee $employee, array $options = []): PayrollEntry
    {
        $options = $this->normalizeOptions($options);

        // Get all DTRs for this employee within the cutoff date range,
        // and recompute late/undertime/is_rest_day from the current schedule first
        $dtrs = $employee->dtrs()
            ->whereBetween('date', [$cutoff->start_date->toDateString(), $cutoff->end_date->toDateString()])
            ->get();

        $dtrService = new DtrComputationService();
        foreach ($dtrs as $dtr) {
            if (! $dtr->time_in) {
                continue;
            }
            $computed = $dtrService->compute(
                $employee,
                $dtr->date->toDateString(),
                $dtr->time_in,
                $dtr->am_out,
                $dtr->pm_in,
                $dtr->time_out,
                $dtr->overtime_hours,
            );
            $dtr->late_mins      = $computed['late_mins'];
            $dtr->undertime_mins = $computed['undertime_mins'];
            $dtr->is_rest_day    = $computed['is_rest_day'];
            $dtr->total_hours    = $computed['total_hours'];
            $dtr->save();
        }

        // Load holidays in this cutoff period, keyed by date string (Y-m-d)
        $holidays = Holiday::whereBetween('date', [$cutoff->start_date->toDateString(), $cutoff->end_date->toDateString()])
            ->get()
            ->keyBy(fn($h) => $h->date->toDateString());

        $rate               = (float) $employee->rate;
        $basicPay           = 0;
        $holidayPay         = 0;
        $overtimePay        = 0;
        $workingDays        = 0;
        $totalHoursWorked   = 0;
        $totalOvertimeHours = 0;

        if ($employee->salary_type === 'daily') {
            $dailyRate  = $rate;
            $hourlyRate = $dailyRate / 8;

            // Collect all dates that have a worked DTR (time_in present)
            $workedDates = $dtrs->filter(fn($d) => $d->time_in)->map(fn($d) => $d->date->toDateString())->values()->toArray();

            $totalBillableHours = 0;

            foreach ($dtrs as $dtr) {
                if (! $dtr->time_in) {
                    continue;
                }

                $hours = (float) $dtr->total_hours;

                // Cap billable regular hours at 8; excess hours only count if staff filed OT
                $billableHours = min($hours, 8.0);
                $totalBillableHours += $billableHours;
                $totalHoursWorked   += $billableHours;

                $dtrOtHours = (float) $dtr->overtime_hours;
                $totalOvertimeHours += $dtrOtHours;

                $holiday = $holidays->get($dtr->date->toDateString());
                $holidayBillableHours = $this->resolveHolidayBillableHours($billableHours, $holiday, $options);

                if ($holiday?->type === 'regular') {
                    // Worked on a Regular Holiday: additional 100% premium on billable hours
                    $holidayPay  += $holidayBillableHours * $hourlyRate;
                    $overtimePay += $dtrOtHours * ($hourlyRate * 2.6);

                } elseif ($holiday?->type === 'special_non_working') {
                    // Worked on a Special Non-Working Day: additional 30% premium on billable hours
                    $holidayPay  += $holidayBillableHours * $hourlyRate * 0.30;
                    $overtimePay += $dtrOtHours * ($hourlyRate * 1.69);

                } else {
                    // Regular working day or Special Working Holiday: normal rates
                    $overtimePay += $dtrOtHours * ($hourlyRate * 1.30);
                }
            }

            $workingDays = $this->computeWorkingDays($totalBillableHours, $options);
            $basicPay    = $workingDays * $dailyRate;

            // Regular holidays NOT worked: daily employee still gets 100% daily rate
            // (Does not apply if holiday falls on a rest day)
            foreach ($holidays as $holiday) {
                if ($holiday->type !== 'regular') {
                    continue;
                }
                $dateStr = $holiday->date->toDateString();
                if (! in_array($dateStr, $workedDates)) {
                    $dtrOnDay  = $dtrs->first(fn($d) => $d->date->toDateString() === $dateStr);
                    $isRestDay = $dtrOnDay?->is_rest_day ?? false;
                    if (! $isRestDay) {
                        $basicPay += $dailyRate;
                    }
                }
            }

        } else {
            // Monthly rate: basic pay = monthly_rate / 2 (semi-monthly)
            $basicPay        = $rate / 2;
            $dailyEquivalent = $rate / 22; // approximate daily for monthly employees
            $hourlyRate      = $rate / (22 * 8);

            foreach ($dtrs as $dtr) {
                if (! $dtr->time_in) {
                    continue;
                }

                $workingDays++;
                $totalHoursWorked   += min((float) $dtr->total_hours, 8.0);
                $dtrOtHours          = (float) $dtr->overtime_hours;
                $totalOvertimeHours += $dtrOtHours;

                $holiday = $holidays->get($dtr->date->toDateString());

                if ($holiday?->type === 'regular') {
                    // Monthly employees already paid via basic_pay; holiday work = +100% daily equivalent
                    $holidayPay  += $dailyEquivalent;
                    $overtimePay += $dtrOtHours * ($hourlyRate * 2.6);

                } elseif ($holiday?->type === 'special_non_working') {
                    // +30% daily equivalent for working on special non-working day
                    $holidayPay  += $dailyEquivalent * 0.30;
                    $overtimePay += $dtrOtHours * ($hourlyRate * 1.69);

                } else {
                    $overtimePay += $dtrOtHours * ($hourlyRate * 1.30);
                }
            }
        }

        // Allowance: daily_amount × working_days for active allowances
        $allowancePay = 0;
        $activeAllowances = $employee->employeeAllowances()->where('active', true)->get();
        foreach ($activeAllowances as $allowance) {
            $allowancePay += (float) $allowance->daily_amount * $workingDays;
        }

        $grossPay = $basicPay + $holidayPay + $overtimePay + $allowancePay;

        // Get or create payroll entry
        $entry = PayrollEntry::firstOrNew([
            'payroll_cutoff_id' => $cutoff->id,
            'employee_id'       => $employee->id,
        ]);

        $entry->fill([
            'basic_pay'           => round($basicPay, 2),
            'overtime_pay'        => round($overtimePay, 2),
            'holiday_pay'         => round($holidayPay, 2),
            'allowance_pay'       => round($allowancePay, 2),
            'late_deduction'      => 0,
            'undertime_deduction' => 0,
            'gross_pay'           => round($grossPay, 2),
            'working_days'          => $workingDays,
            'total_hours_worked'    => round($totalHoursWorked, 2),
            'total_overtime_hours'  => round($totalOvertimeHours, 2),
        ]);
        $entry->save();

        // On first generation (new entry), auto-copy standing deductions.
        // On regeneration (existing entry), preserve all existing deductions and variable deductions.
        if ($entry->wasRecentlyCreated) {
            $cutoffPeriod = $cutoff->end_date->day <= 15 ? 'first' : 'second';

            $standingDeductions = $employee->employeeStandingDeductions()
                ->where('active', true)
                ->where(function ($query) use ($cutoffPeriod) {
                    $query->where('cutoff_period', 'both')
                          ->orWhere('cutoff_period', $cutoffPeriod);
                })
                ->get();

            foreach ($standingDeductions as $sd) {
                PayrollDeduction::create([
                    'payroll_entry_id' => $entry->id,
                    'type'             => $sd->type,
                    'amount'           => $sd->amount,
                    'description'      => $sd->description,
                ]);
            }
        }

        // Always ensure the 7 default variable deductions exist.
        // firstOrCreate by (payroll_entry_id + description) so they're never duplicated,
        // and existing amounts set by the admin survive regeneration.
        $defaultDeductions = [
            'SSS Premium',
            'PHILHEALTH Premium',
            'PAG-IBIG Cont.',
            'Pag-ibig Loan',
            'SSS Loan',
            'Savings',
        ];

        foreach ($defaultDeductions as $label) {
            PayrollEntryVariableDeduction::firstOrCreate(
                ['payroll_entry_id' => $entry->id, 'description' => $label],
                ['amount' => 0],
            );
        }

        $totalDeductions = round(
            (float) $entry->payrollDeductions()->sum('amount') +
            (float) $entry->payrollVariableDeductions()->sum('amount'),
            2
        );

        // Preserve manually added refunds — do not delete them on regeneration
        $totalRefunds = round((float) $entry->payrollRefunds()->sum('amount'), 2);

        $netPay = round($grossPay - $totalDeductions + $totalRefunds, 2);

        $entry->update([
            'total_deductions' => $totalDeductions,
            'net_pay'          => $netPay,
        ]);

        return $entry->fresh();
    }

    private function normalizeOptions(array $options): array
    {
        return array_merge([
            'mode' => 'default',
            // The sheet rounds near-full holiday shifts up to a full 8-hour holiday premium.
            'sheet_holiday_full_day_threshold' => 7.95,
        ], $options);
    }

    private function computeWorkingDays(float $totalBillableHours, array $options): float
    {
        $days = $totalBillableHours / 8;

        if ($options['mode'] === 'sheet') {
            return round($days, 2);
        }

        return floor($days * 100) / 100;
    }

    private function resolveHolidayBillableHours(float $billableHours, ?Holiday $holiday, array $options): float
    {
        if ($options['mode'] !== 'sheet') {
            return $billableHours;
        }

        if (! in_array($holiday?->type, ['regular', 'special_non_working'], true)) {
            return $billableHours;
        }

        if ($billableHours >= $options['sheet_holiday_full_day_threshold']) {
            return 8.0;
        }

        return $billableHours;
    }
}
