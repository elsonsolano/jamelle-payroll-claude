<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DailySchedule;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Services\DtrComputationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeScheduleController extends Controller
{
    public function __construct(private DtrComputationService $computer) {}
    public function index(Employee $employee): View
    {
        $employee->load('branch');
        $defaultSchedule = $employee->employeeSchedules()->where('week_start_date', '2000-01-01')->first();
        $schedules      = $employee->employeeSchedules()->orderByDesc('week_start_date')->get();
        $dailySchedules = DailySchedule::where('employee_id', $employee->id)
            ->with('assignedBranch')
            ->orderByDesc('date')
            ->get();
        $branches = Branch::orderBy('name')->get(['id', 'name']);
        return view('schedules.index', compact('employee', 'schedules', 'dailySchedules', 'branches', 'defaultSchedule'));
    }

    public function saveDefault(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'rest_days'       => 'required|array|min:1',
            'rest_days.*'     => 'string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'work_start_time' => 'nullable|date_format:H:i',
            'work_end_time'   => 'nullable|date_format:H:i',
        ]);

        if (empty($validated['work_start_time']) || empty($validated['work_end_time'])) {
            $validated['work_start_time'] = null;
            $validated['work_end_time']   = null;
        }

        EmployeeSchedule::updateOrCreate(
            ['employee_id' => $employee->id, 'week_start_date' => '2000-01-01'],
            $validated,
        );

        // Recompute all existing DTRs so late/undertime reflects the new default schedule
        $employee->dtrs()->whereNotNull('time_in')->get()->each(function ($dtr) use ($employee) {
            $this->recomputeDtr($employee, $dtr->date);
        });

        return redirect()->route('employees.schedules.index', $employee)
            ->with('success', 'Default schedule saved.');
    }

    public function destroyDefault(Employee $employee): RedirectResponse
    {
        $employee->employeeSchedules()->where('week_start_date', '2000-01-01')->delete();

        return redirect()->route('employees.schedules.index', $employee)
            ->with('success', 'Default schedule removed.');
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'week_start_date'  => 'required|date',
            'rest_days'        => 'required|array|min:1',
            'rest_days.*'      => 'string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'work_start_time'  => 'nullable|date_format:H:i',
            'work_end_time'    => 'nullable|date_format:H:i',
        ]);

        $validated['employee_id'] = $employee->id;

        // Clear times if only one is provided
        if (empty($validated['work_start_time']) || empty($validated['work_end_time'])) {
            $validated['work_start_time'] = null;
            $validated['work_end_time']   = null;
        }

        EmployeeSchedule::create($validated);

        return redirect()->route('employees.schedules.index', $employee)
            ->with('success', 'Schedule added successfully.');
    }

    public function update(Request $request, Employee $employee, EmployeeSchedule $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'week_start_date'  => 'required|date',
            'rest_days'        => 'required|array|min:1',
            'rest_days.*'      => 'string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'work_start_time'  => 'nullable|date_format:H:i',
            'work_end_time'    => 'nullable|date_format:H:i',
        ]);

        if (empty($validated['work_start_time']) || empty($validated['work_end_time'])) {
            $validated['work_start_time'] = null;
            $validated['work_end_time']   = null;
        }

        $schedule->update($validated);

        return redirect()->route('employees.schedules.index', $employee)
            ->with('success', 'Schedule updated successfully.');
    }

    public function destroy(Employee $employee, EmployeeSchedule $schedule): RedirectResponse
    {
        $schedule->delete();
        return redirect()->route('employees.schedules.index', $employee)
            ->with('success', 'Schedule deleted.');
    }

    public function storeDaily(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'date'               => 'required|date',
            'work_start_time'    => 'nullable|date_format:H:i',
            'work_end_time'      => 'nullable|date_format:H:i',
            'assigned_branch_id' => 'nullable|exists:branches,id',
        ]);

        $validated['is_day_off'] = $request->boolean('is_day_off');
        $validated['employee_id'] = $employee->id;

        if ($validated['is_day_off']) {
            $validated['work_start_time']    = null;
            $validated['work_end_time']      = null;
            $validated['assigned_branch_id'] = null;
        } elseif (empty($validated['work_start_time']) || empty($validated['work_end_time'])) {
            $validated['work_start_time'] = null;
            $validated['work_end_time']   = null;
        }

        DailySchedule::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $validated['date']],
            $validated
        );

        $this->recomputeDtr($employee, $validated['date']);

        return redirect()->route('employees.schedules.index', $employee)
            ->with('success', 'Daily schedule added.');
    }

    public function updateDaily(Request $request, Employee $employee, DailySchedule $daily): RedirectResponse
    {
        $validated = $request->validate([
            'date'               => 'required|date',
            'work_start_time'    => 'nullable|date_format:H:i',
            'work_end_time'      => 'nullable|date_format:H:i',
            'assigned_branch_id' => 'nullable|exists:branches,id',
        ]);

        $validated['is_day_off'] = $request->boolean('is_day_off');

        if ($validated['is_day_off']) {
            $validated['work_start_time']    = null;
            $validated['work_end_time']      = null;
            $validated['assigned_branch_id'] = null;
        } elseif (empty($validated['work_start_time']) || empty($validated['work_end_time'])) {
            $validated['work_start_time'] = null;
            $validated['work_end_time']   = null;
        }

        $daily->update($validated);

        $this->recomputeDtr($employee, $validated['date']);

        return redirect()->route('employees.schedules.index', $employee)
            ->with('success', 'Daily schedule updated.');
    }

    public function destroyDaily(Employee $employee, DailySchedule $daily): RedirectResponse
    {
        $daily->delete();
        return redirect()->route('employees.schedules.index', $employee)
            ->with('success', 'Daily schedule deleted.');
    }

    private function recomputeDtr(Employee $employee, string $date): void
    {
        $dtr = Dtr::where('employee_id', $employee->id)->where('date', $date)->first();

        if (! $dtr) {
            return;
        }

        $computed = $this->computer->compute(
            $employee,
            $date,
            $dtr->time_in,
            $dtr->am_out,
            $dtr->pm_in,
            $dtr->time_out,
            $dtr->overtime_hours > 0 ? (float) $dtr->overtime_hours : null,
        );

        $dtr->update([
            'late_mins'      => $computed['late_mins'],
            'undertime_mins' => $computed['undertime_mins'],
            'is_rest_day'    => $computed['is_rest_day'],
        ]);
    }
}
