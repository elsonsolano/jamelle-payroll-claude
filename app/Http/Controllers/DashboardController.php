<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DailySchedule;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\PayrollCutoff;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $totalEmployees    = Employee::where('active', true)->count();
        $totalBranches     = Branch::count();
        $activeCutoffs     = PayrollCutoff::whereIn('status', ['draft', 'processing'])->count();
        $dtrToday          = Dtr::whereDate('date', today())->count();

        $recentCutoffs = PayrollCutoff::with('branch')
            ->orderByDesc('start_date')
            ->limit(5)
            ->get();

        $employeesByBranch = Branch::withCount(['employees' => function ($q) {
            $q->where('active', true);
        }])->get();

        // Calendar events — preloaded for client-side month navigation
        $calendarEvents = $this->buildCalendarEvents();

        $scheduleGrid = $this->buildScheduleGrid();

        $probationEndingSoon = collect();
        if (auth()->user()->isSuperAdmin()) {
            $probationEndingSoon = Employee::where('active', true)
                ->where('employment_status', 'probation')
                ->whereNotNull('probation_end_date')
                ->whereBetween('probation_end_date', [today(), today()->addDays(14)])
                ->orderBy('probation_end_date')
                ->get(['id', 'first_name', 'last_name', 'probation_end_date']);
        }

        return view('dashboard', compact(
            'totalEmployees',
            'totalBranches',
            'activeCutoffs',
            'dtrToday',
            'recentCutoffs',
            'employeesByBranch',
            'calendarEvents',
            'scheduleGrid',
            'probationEndingSoon'
        ));
    }

    private function buildScheduleGrid(): array
    {
        $start = today();
        $end   = today()->addDays(14);

        $dates = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates->push($d->toDateString());
        }

        $branches = Branch::with(['employees' => function ($q) {
            $q->where('active', true)->orderBy('last_name')->orderBy('first_name');
        }])
        ->orderByRaw('CASE WHEN id = 6 THEN 1 ELSE 0 END')
        ->orderBy('name')
        ->get();

        $allEmployeeIds = $branches->flatMap(fn ($b) => $b->employees->pluck('id'));

        // Bulk-load DailySchedules for the 15-day window
        $dailyByEmployee = DailySchedule::whereIn('employee_id', $allEmployeeIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($ds) => $ds->date->toDateString()));

        // Bulk-load all EmployeeSchedules for fallback (newest first)
        $weeklyByEmployee = EmployeeSchedule::whereIn('employee_id', $allEmployeeIds)
            ->orderByDesc('week_start_date')
            ->get()
            ->groupBy('employee_id');

        $grid = [];
        foreach ($branches as $branch) {
            $employeeRows = [];
            foreach ($branch->employees as $employee) {
                $days = [];
                foreach ($dates as $dateStr) {
                    $days[$dateStr] = $this->resolveDay(
                        $employee->id,
                        $dateStr,
                        $dailyByEmployee,
                        $weeklyByEmployee,
                    );
                }
                $employeeRows[] = ['name' => $employee->full_name, 'days' => $days];
            }
            $grid[] = ['branch' => $branch->name, 'employees' => $employeeRows];
        }

        return ['dates' => $dates->all(), 'branches' => $grid];
    }

    private function resolveDay(int $employeeId, string $dateStr, Collection $dailyByEmployee, Collection $weeklyByEmployee): array
    {
        // DailySchedule takes priority
        $ds = $dailyByEmployee->get($employeeId)?->get($dateStr);
        if ($ds) {
            return [
                'status' => $ds->is_day_off ? 'off' : 'working',
                'start'  => $ds->work_start_time,
                'end'    => $ds->work_end_time,
                'notes'  => $ds->notes,
            ];
        }

        // Fall back to most recent EmployeeSchedule covering this date
        $date     = Carbon::parse($dateStr);
        $schedule = $weeklyByEmployee->get($employeeId, collect())
            ->first(fn ($s) => $s->week_start_date->lte($date));

        if (! $schedule) {
            return ['status' => 'none', 'start' => null, 'end' => null, 'notes' => null];
        }

        $restDays = $schedule->rest_days ?? ['Sunday'];
        if (in_array($date->dayName, $restDays)) {
            return ['status' => 'off', 'start' => null, 'end' => null, 'notes' => null];
        }

        return [
            'status' => 'working',
            'start'  => $schedule->work_start_time,
            'end'    => $schedule->work_end_time,
            'notes'  => null,
        ];
    }

    private function buildCalendarEvents(): array
    {
        $holidays = Holiday::orderBy('date')->get()->map(fn ($h) => [
            'date' => $h->date->format('Y-m-d'),
            'name' => $h->name,
            'type' => $h->type,
        ])->values()->toArray();

        $employees = Employee::where('active', true)
            ->select('first_name', 'last_name', 'birthday', 'hired_date')
            ->get();

        $birthdays = $employees
            ->filter(fn ($e) => $e->birthday !== null)
            ->map(fn ($e) => [
                'month' => (int) $e->birthday->month,
                'day'   => (int) $e->birthday->day,
                'name'  => $e->full_name,
            ])->values()->toArray();

        $anniversaries = $employees
            ->filter(fn ($e) => $e->hired_date !== null)
            ->map(fn ($e) => [
                'month'      => (int) $e->hired_date->month,
                'day'        => (int) $e->hired_date->day,
                'name'       => $e->full_name,
                'hire_year'  => (int) $e->hired_date->year,
            ])->values()->toArray();

        return compact('holidays', 'birthdays', 'anniversaries');
    }
}
