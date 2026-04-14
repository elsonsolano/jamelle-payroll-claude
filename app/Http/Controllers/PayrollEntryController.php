<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollCutoff;
use App\Models\PayrollEntry;
use App\Notifications\PayslipAvailable;
use App\Services\PayrollComputationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PayrollEntryController extends Controller
{
    public function __construct(protected PayrollComputationService $payrollService)
    {
    }

    public function index(PayrollCutoff $cutoff): View
    {
        $entries = PayrollEntry::with('employee')
            ->where('payroll_cutoff_id', $cutoff->id)
            ->join('employees', 'employees.id', '=', 'payroll_entries.employee_id')
            ->orderBy('employees.last_name')
            ->select('payroll_entries.*')
            ->paginate(30);
        return view('payroll.entries.index', compact('cutoff', 'entries'));
    }

    public function show(PayrollCutoff $cutoff, PayrollEntry $entry): View
    {
        $entry->load('employee.branch', 'payrollDeductions', 'payrollVariableDeductions', 'payrollRefunds');

        $employee = $entry->employee;
        $rate     = (float) $employee->rate;

        $dtrs = $employee->dtrs()
            ->whereBetween('date', [$cutoff->start_date->toDateString(), $cutoff->end_date->toDateString()])
            ->orderBy('date')
            ->get();

        $holidays = \App\Models\Holiday::whereBetween('date', [
            $cutoff->start_date->toDateString(),
            $cutoff->end_date->toDateString(),
        ])->get()->keyBy(fn($h) => $h->date->toDateString());

        $breakdown = $this->buildBreakdown($employee, $rate, $dtrs, $holidays);

        return view('payroll.entries.show', compact('cutoff', 'entry', 'breakdown'));
    }

    private function buildBreakdown($employee, float $rate, $dtrs, $holidays): array
    {
        $workedDates = $dtrs->filter(fn($d) => $d->time_in)->map(fn($d) => $d->date->toDateString())->toArray();

        if ($employee->salary_type === 'daily') {
            $hourlyRate = $rate / 8;
            $dtrRows = [];
            $totalBillable = 0;
            $otRows = [];
            $holidayRows = [];

            foreach ($dtrs->filter(fn($d) => $d->time_in) as $dtr) {
                $dateStr  = $dtr->date->toDateString();
                $billable = min((float) $dtr->total_hours, 8.0);
                $totalBillable += $billable;
                $holiday  = $holidays->get($dateStr);

                $dtrRows[] = [
                    'date'       => $dtr->date->format('M d'),
                    'day'        => $dtr->date->format('D'),
                    'hours'      => (float) $dtr->total_hours,
                    'billable'   => $billable,
                    'is_rest'    => $dtr->is_rest_day,
                    'holiday'    => $holiday?->name,
                    'late_mins'  => $dtr->late_mins,
                ];

                // OT row
                $otHours = (float) $dtr->overtime_hours;
                if ($otHours > 0) {
                    if ($holiday?->type === 'regular') {
                        $mult = 2.60;
                    } elseif ($holiday?->type === 'special_non_working') {
                        $mult = 1.69;
                    } else {
                        $mult = 1.30;
                    }
                    $otRows[] = [
                        'date'    => $dtr->date->format('M d'),
                        'hours'   => $otHours,
                        'mult'    => $mult,
                        'amount'  => round($otHours * $hourlyRate * $mult, 2),
                        'holiday' => $holiday?->name,
                    ];
                }

                // Holiday pay row (worked)
                if ($holiday && $holiday->type !== 'special_working') {
                    $premium = $holiday->type === 'regular' ? 1.00 : 0.30;
                    $holidayRows[] = [
                        'date'    => $dtr->date->format('M d'),
                        'name'    => $holiday->name,
                        'type'    => $holiday->type,
                        'worked'  => true,
                        'hours'   => $billable,
                        'premium' => $premium,
                        'amount'  => round($billable * $hourlyRate * $premium, 2),
                    ];
                }
            }

            $workingDays  = floor($totalBillable / 8 * 100) / 100;
            $basePay      = $workingDays * $rate;

            // Unworked regular holidays
            $unworkedHolidays = [];
            foreach ($holidays as $holiday) {
                if ($holiday->type !== 'regular') continue;
                $dateStr = $holiday->date->toDateString();
                if (!in_array($dateStr, $workedDates)) {
                    $dtrOnDay  = $dtrs->first(fn($d) => $d->date->toDateString() === $dateStr);
                    if (!($dtrOnDay?->is_rest_day ?? false)) {
                        $unworkedHolidays[] = [
                            'date'   => $holiday->date->format('M d'),
                            'name'   => $holiday->name,
                            'amount' => $rate,
                        ];
                    }
                }
            }

            return [
                'salary_type'       => 'daily',
                'rate'              => $rate,
                'hourly_rate'       => round($hourlyRate, 4),
                'dtr_rows'          => $dtrRows,
                'total_billable'    => $totalBillable,
                'working_days'      => $workingDays,
                'base_pay'          => round($basePay, 2),
                'unworked_holidays' => $unworkedHolidays,
                'ot_rows'           => $otRows,
                'holiday_rows'      => $holidayRows,
            ];
        }

        // Monthly
        $hourlyRate = $rate / (22 * 8);
        $dailyEquiv = $rate / 22;
        $otRows = [];
        $holidayRows = [];

        foreach ($dtrs->filter(fn($d) => $d->time_in) as $dtr) {
            $dateStr = $dtr->date->toDateString();
            $holiday = $holidays->get($dateStr);
            $otHours = (float) $dtr->overtime_hours;
            if ($otHours > 0) {
                $mult = match($holiday?->type) {
                    'regular'            => 2.60,
                    'special_non_working' => 1.69,
                    default              => 1.30,
                };
                $otRows[] = ['date' => $dtr->date->format('M d'), 'hours' => $otHours, 'mult' => $mult, 'amount' => round($otHours * $hourlyRate * $mult, 2)];
            }
            if ($holiday && in_array($holiday->type, ['regular', 'special_non_working'])) {
                $premium = $holiday->type === 'regular' ? 1.00 : 0.30;
                $holidayRows[] = [
                    'date'    => $dtr->date->format('M d'), 'name' => $holiday->name,
                    'type'    => $holiday->type, 'worked' => true,
                    'hours'   => null,
                    'premium' => $premium, 'amount' => round($dailyEquiv * $premium, 2),
                ];
            }
        }

        return [
            'salary_type'       => 'monthly',
            'rate'              => $rate,
            'hourly_rate'       => round($hourlyRate, 4),
            'daily_equiv'       => round($dailyEquiv, 4),
            'base_pay'          => round($rate / 2, 2),
            'dtr_rows'          => [],
            'total_billable'    => 0,
            'working_days'      => $dtrs->filter(fn($d) => $d->time_in)->count(),
            'unworked_holidays' => [],
            'ot_rows'           => $otRows,
            'holiday_rows'      => $holidayRows,
        ];
    }

    public function pdf(PayrollCutoff $cutoff, PayrollEntry $entry): \Illuminate\Http\Response
    {
        $entry->load('employee.branch', 'payrollDeductions', 'payrollVariableDeductions', 'payrollRefunds');

        $pdf = Pdf::loadView('payroll.entries.pdf', compact('cutoff', 'entry'))
            ->setPaper('a4', 'portrait');

        $filename = 'payslip-' . str($entry->employee->full_name)->slug() . '-' . $cutoff->start_date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function generate(Request $request, PayrollCutoff $cutoff): RedirectResponse
    {
        $mode = $request->input('mode', 'default');

        if (! in_array($mode, ['default', 'sheet'], true)) {
            $mode = 'default';
        }

        return $this->generateForMode($cutoff, $mode);
    }

    public function finalize(PayrollCutoff $cutoff): RedirectResponse
    {
        if ($cutoff->status !== 'processing') {
            return redirect()->route('payroll.cutoffs.show', $cutoff)
                ->with('error', 'Only a payroll in preview (Processing) state can be finalized.');
        }

        $cutoff->update(['status' => 'finalized']);

        // Notify each staff member who has an entry in this cutoff
        $cutoff->payrollEntries()
            ->with('employee.user')
            ->get()
            ->each(function ($entry) use ($cutoff) {
                $user = $entry->employee?->user;
                if ($user && $user->role === 'staff') {
                    $user->notify(new PayslipAvailable($cutoff));
                }
            });

        return redirect()->route('payroll.cutoffs.show', $cutoff)
            ->with('success', 'Payroll finalized. Staff DTRs in this period are now locked.');
    }

    private function generateForMode(PayrollCutoff $cutoff, string $mode): RedirectResponse
    {
        if ($cutoff->status === 'voided') {
            return redirect()->route('payroll.cutoffs.show', $cutoff)
                ->with('error', 'Cannot regenerate a voided payroll cutoff.');
        }

        $cutoff->update(['status' => 'processing']);

        $employees = Employee::where('branch_id', $cutoff->branch_id)
            ->where('active', true)
            ->get();

        foreach ($employees as $employee) {
            $this->payrollService->computeEntry($cutoff, $employee, ['mode' => $mode]);
        }

        $message = $mode === 'sheet'
            ? 'Payroll recomputed using sheet logic for '
            : 'Payroll computed for ';

        return redirect()->route('payroll.cutoffs.show', $cutoff)
            ->with('success', $message . $employees->count() . ' employee(s). Review the numbers, then click Finalize when ready.');
    }
}
