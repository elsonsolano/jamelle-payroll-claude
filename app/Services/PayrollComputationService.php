<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\PayrollCutoff;
use App\Models\PayrollDeduction;
use App\Models\PayrollEntry;
use App\Services\DtrComputationService;

class PayrollComputationService
{
    public function computeEntry(PayrollCutoff $cutoff, Employee $employee): PayrollEntry
    {
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

        $rate             = (float) $employee->rate;
        $basicPay         = 0;
        $holidayPay       = 0;
        $overtimePay      = 0;
        $workingDays      = 0;
        $totalHoursWorked = 0;

        if ($employee->salary_type === 'daily') {
            $dailyRate  = $rate;
            $hourlyRate = $dailyRate / 8;

            // Collect all dates that have a worked DTR (time_in present)
            $workedDates = $dtrs->filter(fn($d) => $d->time_in)->map(fn($d) => $d->date->toDateString())->values()->toArray();

            foreach ($dtrs as $dtr) {
                if (! $dtr->time_in) {
                    continue;
                }

                $workingDays++;
                $hours    = (float) $dtr->total_hours;
                $totalHoursWorked += $hours;

                $holiday = $holidays->get($dtr->date->toDateString());

                // Basic pay based on actual hours worked — reduced hours naturally mean less pay
                $basicPay += $hours * $hourlyRate;

                if ($holiday?->type === 'regular') {
                    // Worked on a Regular Holiday: additional 100% premium on hours worked
                    $holidayPay  += $hours * $hourlyRate;
                    $overtimePay += (float) $dtr->overtime_hours * ($hourlyRate * 2.6);

                } elseif ($holiday?->type === 'special_non_working') {
                    // Worked on a Special Non-Working Day: additional 30% premium on hours worked
                    $holidayPay  += $hours * $hourlyRate * 0.30;
                    $overtimePay += (float) $dtr->overtime_hours * ($hourlyRate * 1.69);

                } else {
                    // Regular working day or Special Working Holiday: normal rates
                    $otMultiplier = $dtr->is_rest_day ? 1.30 : 1.25;
                    $overtimePay += (float) $dtr->overtime_hours * ($hourlyRate * $otMultiplier);
                }
            }

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
                $totalHoursWorked += (float) $dtr->total_hours;

                $holiday = $holidays->get($dtr->date->toDateString());

                if ($holiday?->type === 'regular') {
                    // Monthly employees already paid via basic_pay; holiday work = +100% daily equivalent
                    $holidayPay  += $dailyEquivalent;
                    $overtimePay += (float) $dtr->overtime_hours * ($hourlyRate * 2.6);

                } elseif ($holiday?->type === 'special_non_working') {
                    // +30% daily equivalent for working on special non-working day
                    $holidayPay  += $dailyEquivalent * 0.30;
                    $overtimePay += (float) $dtr->overtime_hours * ($hourlyRate * 1.69);

                } else {
                    $otMultiplier = $dtr->is_rest_day ? 1.30 : 1.25;
                    $overtimePay += (float) $dtr->overtime_hours * ($hourlyRate * $otMultiplier);
                }
            }
        }

        $grossPay = $basicPay + $holidayPay + $overtimePay;

        // Get or create payroll entry
        $entry = PayrollEntry::firstOrNew([
            'payroll_cutoff_id' => $cutoff->id,
            'employee_id'       => $employee->id,
        ]);

        $entry->fill([
            'basic_pay'           => round($basicPay, 2),
            'overtime_pay'        => round($overtimePay, 2),
            'holiday_pay'         => round($holidayPay, 2),
            'late_deduction'      => 0,
            'undertime_deduction' => 0,
            'gross_pay'           => round($grossPay, 2),
            'working_days'        => $workingDays,
            'total_hours_worked'  => round($totalHoursWorked, 2),
        ]);
        $entry->save();

        // Determine if this is the first or second cutoff of the month.
        $cutoffPeriod = $cutoff->end_date->day <= 15 ? 'first' : 'second';

        // Apply standing deductions
        $standingDeductions = $employee->employeeStandingDeductions()
            ->where('active', true)
            ->where(function ($query) use ($cutoffPeriod) {
                $query->where('cutoff_period', 'both')
                      ->orWhere('cutoff_period', $cutoffPeriod);
            })
            ->get();

        // Remove existing payroll deductions to avoid duplicates
        $entry->payrollDeductions()->delete();

        $totalDeductionAmount = 0;

        foreach ($standingDeductions as $sd) {
            PayrollDeduction::create([
                'payroll_entry_id' => $entry->id,
                'type'             => $sd->type,
                'amount'           => $sd->amount,
                'description'      => $sd->description,
            ]);
            $totalDeductionAmount += (float) $sd->amount;
        }

        $totalDeductions = round($totalDeductionAmount, 2);
        $netPay          = round($grossPay - $totalDeductions, 2);

        $entry->update([
            'total_deductions' => $totalDeductions,
            'net_pay'          => $netPay,
        ]);

        return $entry->fresh();
    }
}
