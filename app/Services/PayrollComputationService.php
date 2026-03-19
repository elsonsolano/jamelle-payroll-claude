<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\PayrollCutoff;
use App\Models\PayrollDeduction;
use App\Models\PayrollEntry;

class PayrollComputationService
{
    public function computeEntry(PayrollCutoff $cutoff, Employee $employee): PayrollEntry
    {
        // Get all DTRs for this employee within the cutoff date range
        $dtrs = $employee->dtrs()
            ->whereBetween('date', [$cutoff->start_date->toDateString(), $cutoff->end_date->toDateString()])
            ->get();

        // Load holidays in this cutoff period, keyed by date string (Y-m-d)
        $holidays = Holiday::whereBetween('date', [$cutoff->start_date->toDateString(), $cutoff->end_date->toDateString()])
            ->get()
            ->keyBy(fn($h) => $h->date->toDateString());

        $rate             = (float) $employee->rate;
        $basicPay         = 0;
        $holidayPay       = 0;
        $lateDed          = 0;
        $undertimeDed     = 0;
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
                $totalHoursWorked += (float) $dtr->total_hours;

                $holiday = $holidays->get($dtr->date->toDateString());

                // Late & undertime deductions apply on all days including holidays
                $lateDed      += ($dtr->late_mins / 60) * $hourlyRate;
                $undertimeDed += ($dtr->undertime_mins / 60) * $hourlyRate;

                if ($holiday?->type === 'regular') {
                    // Worked on a Regular Holiday: 200% daily rate
                    // Basic pay covers the 1st 100% (counted via workingDays * dailyRate below)
                    // Holiday pay covers the extra 100%
                    $holidayPay  += $dailyRate;
                    // OT: 200% base × 130% = 260% of regular hourly rate
                    $overtimePay += (float) $dtr->overtime_hours * ($hourlyRate * 2.6);

                } elseif ($holiday?->type === 'special_non_working') {
                    // Worked on a Special Non-Working Day: 130% daily rate
                    // Basic pay covers 100%, holiday pay covers the extra 30%
                    $holidayPay  += $dailyRate * 0.30;
                    // OT: 130% base × 130% = 169% of regular hourly rate
                    $overtimePay += (float) $dtr->overtime_hours * ($hourlyRate * 1.69);

                } else {
                    // Regular working day or Special Working Holiday: normal rates
                    $otMultiplier = $dtr->is_rest_day ? 1.30 : 1.25;
                    $overtimePay += (float) $dtr->overtime_hours * ($hourlyRate * $otMultiplier);
                }
            }

            // Basic pay = days worked × daily rate
            $basicPay = $workingDays * $dailyRate;

            // Regular holidays NOT worked: daily employee still gets 100% daily rate
            // (Does not apply if holiday falls on a rest day — checked via existing DTR with is_rest_day)
            foreach ($holidays as $holiday) {
                if ($holiday->type !== 'regular') {
                    continue;
                }
                $dateStr = $holiday->date->toDateString();
                if (! in_array($dateStr, $workedDates)) {
                    // Check if we have a rest-day DTR for this date
                    $dtrOnDay = $dtrs->first(fn($d) => $d->date->toDateString() === $dateStr);
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
            'late_deduction'      => round($lateDed, 2),
            'undertime_deduction' => round($undertimeDed, 2),
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

        $totalDeductionAmount = $lateDed + $undertimeDed;

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
