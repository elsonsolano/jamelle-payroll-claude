<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DailySchedule;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\PayrollEntry;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function overtime(Request $request)
    {
        $branches = Branch::orderBy('name')->get();

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $branchId = $request->input('branch_id');
        $search = $request->input('search');
        $status = $request->input('status'); // approved | pending | rejected | '' (all)

        $query = Dtr::query()
            ->where('ot_status', '!=', 'none')
            ->where('overtime_hours', '>', 0)
            ->whereBetween('date', [$from, $to])
            ->with(['employee.branch', 'approvedBy'])
            ->orderBy('date');

        if ($branchId) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $branchId));
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$search}%"]);
            });
        }

        if ($status) {
            $query->where('ot_status', $status);
        }

        $grouped = $query->get()
            ->groupBy('employee_id')
            ->map(function ($dtrs) {
                $employee = $dtrs->first()->employee;

                return [
                    'employee' => $employee,
                    'occurrences' => $dtrs->count(),
                    'total_ot_hours' => $dtrs->sum('overtime_hours'),
                    'pending_count' => $dtrs->where('ot_status', 'pending')->count(),
                    'dtrs' => $dtrs->sortBy('date')->values(),
                ];
            })
            ->sortBy(fn ($row) => $row['employee']->full_name)
            ->values();

        return view('reports.overtime', compact('branches', 'grouped', 'from', 'to', 'branchId', 'search', 'status'));
    }

    public function lates(Request $request)
    {
        $branches = Branch::orderBy('name')->get();

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $branchId = $request->input('branch_id');
        $search = $request->input('search');

        $query = Dtr::query()
            ->where('late_mins', '>', 0)
            ->whereBetween('date', [$from, $to])
            ->with('employee.branch')
            ->orderBy('date');

        if ($branchId) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $branchId));
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$search}%"]);
            });
        }

        $grouped = $query->get()
            ->groupBy('employee_id')
            ->map(function ($dtrs) {
                $employee = $dtrs->first()->employee;

                return [
                    'employee' => $employee,
                    'occurrences' => $dtrs->count(),
                    'total_late_mins' => $dtrs->sum('late_mins'),
                    'dtrs' => $dtrs->sortBy('date')->values(),
                ];
            })
            ->sortBy(fn ($row) => $row['employee']->full_name)
            ->values();

        return view('reports.lates', compact('branches', 'grouped', 'from', 'to', 'branchId', 'search'));
    }

    public function absences(Request $request)
    {
        $branches = Branch::orderBy('name')->get();

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $branchId = $request->input('branch_id');
        $search = $request->input('search');

        $from = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $to = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $today = now()->toDateString();
        if ($to > $today) {
            $to = $today;
        }

        $employeeQuery = Employee::with('branch')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($search, function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$search}%"]);
            });

        $employees = $employeeQuery->get();
        $employeeIds = $employees->pluck('id')->all();

        $dailySchedulesByEmployee = DailySchedule::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$from, $to])
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($ds) => $ds->date->toDateString()));

        $employeeSchedulesByEmployee = EmployeeSchedule::whereIn('employee_id', $employeeIds)
            ->where('week_start_date', '<=', $to)
            ->orderBy('week_start_date')
            ->get()
            ->groupBy('employee_id');

        $holidayDates = Holiday::whereBetween('date', [$from, $to])
            ->pluck('date')
            ->map(fn ($date) => $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString())
            ->flip();

        $dtrDatesByEmployee = Dtr::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$from, $to])
            ->select('employee_id', 'date')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->pluck('date')
                ->map(fn ($d) => $d instanceof Carbon ? $d->toDateString() : (string) $d)
                ->flip()
            );

        $dateRange = CarbonPeriod::create($from, $to);

        $grouped = [];
        foreach ($employees as $employee) {
            $empDailySchedules = $dailySchedulesByEmployee->get($employee->id, collect());
            $empWeeklySchedules = $employeeSchedulesByEmployee->get($employee->id, collect());
            $empDtrDates = $dtrDatesByEmployee->get($employee->id, collect());

            if ($empDailySchedules->isEmpty() && $empWeeklySchedules->isEmpty()) {
                continue;
            }

            $absences = [];
            foreach ($dateRange as $date) {
                $dateStr = $date->toDateString();
                $dayName = $date->format('l');

                $daily = $empDailySchedules->get($dateStr);
                if ($daily) {
                    if ($daily->is_day_off) {
                        continue;
                    }
                    $workStart = $daily->work_start_time;
                    $workEnd = $daily->work_end_time;
                } else {
                    if ($holidayDates->has($dateStr)) {
                        continue;
                    }

                    $schedule = $empWeeklySchedules
                        ->filter(fn ($s) => $s->week_start_date->toDateString() <= $dateStr)
                        ->last();

                    if (! $schedule) {
                        continue;
                    }

                    if (in_array($dayName, $schedule->rest_days ?? [])) {
                        continue;
                    }

                    $workStart = $schedule->work_start_time;
                    $workEnd = $schedule->work_end_time;
                }

                if ($empDtrDates->has($dateStr)) {
                    continue;
                }

                $absences[] = [
                    'date' => $dateStr,
                    'work_start_time' => $workStart,
                    'work_end_time' => $workEnd,
                ];
            }

            if (! empty($absences)) {
                $grouped[] = [
                    'employee' => $employee,
                    'occurrences' => count($absences),
                    'absences' => $absences,
                ];
            }
        }

        usort($grouped, fn ($a, $b) => strcmp($a['employee']->full_name, $b['employee']->full_name));

        $months = collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create(null, $m)->format('F')]);
        $years = range(2026, max(now()->year, 2026));

        return view('reports.absences', compact('branches', 'grouped', 'month', 'year', 'months', 'years', 'branchId', 'search'));
    }

    public function thirteenthMonth(Request $request)
    {
        $branches = Branch::orderBy('name')->get();

        $year = (int) $request->input('year', now()->year);
        $branchId = $request->input('branch_id');
        $search = $request->input('search');

        $query = PayrollEntry::query()
            ->whereHas('payrollCutoff', fn ($q) => $q
                ->where('status', 'finalized')
                ->whereYear('end_date', $year)
            )
            ->with(['employee.branch', 'payrollCutoff'])
            ->orderBy('id');

        if ($branchId) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $branchId));
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$search}%"]);
            });
        }

        $grouped = $query->get()
            ->groupBy('employee_id')
            ->map(function ($entries) {
                $employee = $entries->first()->employee;
                $total_basic_pay = $entries->sum('basic_pay');

                return [
                    'employee' => $employee,
                    'total_basic_pay' => $total_basic_pay,
                    'thirteenth_month' => $total_basic_pay / 12,
                    'cutoff_count' => $entries->count(),
                    'entries' => $entries->sortBy('payrollCutoff.end_date')->values(),
                ];
            })
            ->sortBy(fn ($row) => $row['employee']->full_name)
            ->values();

        $years = range(now()->year, max(now()->year - 5, 2020));

        return view('reports.thirteenth-month', compact('branches', 'grouped', 'year', 'years', 'branchId', 'search'));
    }

    public function phic(Request $request)
    {
        $branches = Branch::orderBy('name')->get();

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $branchId = $request->input('branch_id');
        $search = $request->input('search');

        $query = PayrollEntry::query()
            ->whereHas('payrollCutoff', fn ($q) => $q
                ->where('status', 'finalized')
                ->whereMonth('end_date', $month)
                ->whereYear('end_date', $year)
            )
            ->whereHas('payrollVariableDeductions', fn ($q) => $q
                ->where('description', 'PHILHEALTH Premium')
                ->where('amount', '>', 0)
            )
            ->with([
                'employee.branch',
                'payrollCutoff',
                'payrollVariableDeductions' => fn ($q) => $q->where('description', 'PHILHEALTH Premium'),
            ]);

        if ($branchId) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $branchId));
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$search}%"]);
            });
        }

        $grouped = $query->get()
            ->groupBy('employee_id')
            ->map(function ($entries) {
                $employeeShare = $entries->sum(fn ($entry) => (float) $entry->payrollVariableDeductions->sum('amount'));

                return [
                    'employee' => $entries->first()->employee,
                    'employee_share' => round($employeeShare, 2),
                    'employer_share' => round($employeeShare, 2),
                ];
            })
            ->sortBy(fn ($row) => $row['employee']->full_name)
            ->values();

        $months = collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create(null, $m)->format('F')]);
        $years = range(now()->year, max(now()->year - 5, 2020));
        $grandTotal = round($grouped->sum('employee_share') + $grouped->sum('employer_share'), 2);

        return view('reports.phic', compact('branches', 'grouped', 'month', 'year', 'months', 'years', 'branchId', 'search', 'grandTotal'));
    }
}
