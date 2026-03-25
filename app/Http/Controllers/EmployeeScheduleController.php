<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DailySchedule;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeScheduleController extends Controller
{
    public function index(Employee $employee): View
    {
        $employee->load('branch');
        $schedules      = $employee->employeeSchedules()->orderByDesc('week_start_date')->get();
        $dailySchedules = DailySchedule::where('employee_id', $employee->id)
            ->with('assignedBranch')
            ->orderByDesc('date')
            ->get();
        $branches = Branch::orderBy('name')->get(['id', 'name']);
        return view('schedules.index', compact('employee', 'schedules', 'dailySchedules', 'branches'));
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

        return redirect()->route('employees.schedules.index', $employee)
            ->with('success', 'Daily schedule updated.');
    }

    public function destroyDaily(Employee $employee, DailySchedule $daily): RedirectResponse
    {
        $daily->delete();
        return redirect()->route('employees.schedules.index', $employee)
            ->with('success', 'Daily schedule deleted.');
    }
}
