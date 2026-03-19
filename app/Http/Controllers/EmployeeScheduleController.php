<?php

namespace App\Http\Controllers;

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
        $schedules = $employee->employeeSchedules()->orderByDesc('week_start_date')->get();
        return view('schedules.index', compact('employee', 'schedules'));
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
}
