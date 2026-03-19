<?php

namespace App\Services;

use App\Models\Employee;
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

        $rate       = (float) $employee->rate;
        $basicPay   = 0;
        $lateDed    = 0;
        $undertimeDed = 0;
        $overtimePay  = 0;
        $workingDays  = 0;
        $totalHoursWorked = 0;

        if ($employee->salary_type === 'daily') {
            $dailyRate  = $rate;
            $hourlyRate = $dailyRate / 8;

            foreach ($dtrs as $dtr) {
                if ($dtr->time_in) {
                    $workingDays++;
                    $totalHoursWorked += (float) $dtr->total_hours;

                    // Late deduction: late_mins / 60 * hourly_rate
                    $lateDed += ($dtr->late_mins / 60) * $hourlyRate;

                    // Undertime deduction
                    $undertimeDed += ($dtr->undertime_mins / 60) * $hourlyRate;

                    // Overtime pay: rest days get 1.30x, regular days 1.25x
                    $otMultiplier = $dtr->is_rest_day ? 1.30 : 1.25;
                    $overtimePay += (float) $dtr->overtime_hours * ($hourlyRate * $otMultiplier);
                }
            }

            $basicPay = $workingDays * $dailyRate;

        } else {
            // Monthly rate: basic pay = monthly_rate / 2 (semi-monthly, no absence deductions)
            $basicPay     = $rate / 2;
            $hourlyRate   = $rate / (22 * 8); // approximate hourly for OT

            foreach ($dtrs as $dtr) {
                if ($dtr->time_in) {
                    $workingDays++;
                    $totalHoursWorked += (float) $dtr->total_hours;

                    // Overtime pay for monthly employees
                    $otMultiplier = $dtr->is_rest_day ? 1.30 : 1.25;
                    $overtimePay += (float) $dtr->overtime_hours * ($hourlyRate * $otMultiplier);
                }
            }
        }

        $grossPay = $basicPay + $overtimePay;

        // Get or create payroll entry
        $entry = PayrollEntry::firstOrNew([
            'payroll_cutoff_id' => $cutoff->id,
            'employee_id'       => $employee->id,
        ]);

        $entry->fill([
            'basic_pay'           => round($basicPay, 2),
            'overtime_pay'        => round($overtimePay, 2),
            'late_deduction'      => round($lateDed, 2),
            'undertime_deduction' => round($undertimeDed, 2),
            'gross_pay'           => round($grossPay, 2),
            'working_days'        => $workingDays,
            'total_hours_worked'  => round($totalHoursWorked, 2),
        ]);
        $entry->save();

        // Determine if this is the first or second cutoff of the month.
        // Payday 15th cutoff ends around the 13th (end_date day <= 15) → "first".
        // Payday 30th/31st cutoff ends around the 29th (end_date day > 15) → "second".
        $cutoffPeriod = $cutoff->end_date->day <= 15 ? 'first' : 'second';

        // Apply standing deductions (active only, matching this cutoff period)
        $standingDeductions = $employee->employeeStandingDeductions()
            ->where('active', true)
            ->where(function ($query) use ($cutoffPeriod) {
                $query->where('cutoff_period', 'both')
                      ->orWhere('cutoff_period', $cutoffPeriod);
            })
            ->get();

        // Remove existing payroll deductions for this entry to avoid duplicates
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
        $netPay = round($grossPay - $totalDeductions, 2);

        $entry->update([
            'total_deductions' => $totalDeductions,
            'net_pay'          => $netPay,
        ]);

        return $entry->fresh();
    }
}
