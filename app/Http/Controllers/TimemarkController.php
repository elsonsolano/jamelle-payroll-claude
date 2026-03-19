<?php

namespace App\Http\Controllers;

use App\Jobs\FetchAttendanceJob;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\TimemarkLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TimemarkController extends Controller
{
    public function index(Request $request): View
    {
        $query = TimemarkLog::with('employee.branch');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $request->branch_id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs      = $query->orderByDesc('fetched_at')->paginate(30)->withQueryString();
        $employees = Employee::with('branch')->orderBy('last_name')->orderBy('first_name')->get();
        $branches  = Branch::orderBy('name')->get();

        return view('timemark.logs', compact('logs', 'employees', 'branches'));
    }

    public function fetch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fetch_type'  => 'required|in:employee,branch',
            'employee_id' => 'required_if:fetch_type,employee|nullable|exists:employees,id',
            'branch_id'   => 'required_if:fetch_type,branch|nullable|exists:branches,id',
            'date_from'   => 'required|date',
            'date_to'     => 'required|date|after_or_equal:date_from',
        ]);

        if ($validated['fetch_type'] === 'employee') {
            $employee = Employee::findOrFail($validated['employee_id']);
            FetchAttendanceJob::dispatch($employee, $validated['date_from'], $validated['date_to']);
            return back()->with('success', "Fetch job dispatched for {$employee->full_name}.");
        }

        $employees = Employee::where('branch_id', $validated['branch_id'])
            ->where('active', true)
            ->get();

        if ($employees->isEmpty()) {
            return back()->with('error', 'No active employees found in this branch.');
        }

        foreach ($employees as $employee) {
            FetchAttendanceJob::dispatch($employee, $validated['date_from'], $validated['date_to']);
        }

        $branch = Branch::find($validated['branch_id']);
        return back()->with('success', "Fetch jobs dispatched for {$employees->count()} employees in {$branch->name}.");
    }
}
