<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAllowance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeAllowanceController extends Controller
{
    public function index(Employee $employee): View
    {
        $employee->load('branch');
        $allowances = $employee->employeeAllowances()->orderByDesc('active')->orderBy('id')->get();
        return view('employees.allowances.index', compact('employee', 'allowances'));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'daily_amount' => 'required|numeric|min:0',
            'description'  => 'nullable|string|max:255',
        ]);

        $employee->employeeAllowances()->create(array_merge($validated, ['active' => true]));

        return redirect()->route('employees.allowances.index', $employee)
            ->with('success', 'Allowance added successfully.');
    }

    public function update(Request $request, Employee $employee, EmployeeAllowance $allowance): RedirectResponse
    {
        $validated = $request->validate([
            'daily_amount' => 'required|numeric|min:0',
            'description'  => 'nullable|string|max:255',
        ]);

        $allowance->update($validated);

        return redirect()->route('employees.allowances.index', $employee)
            ->with('success', 'Allowance updated.');
    }

    public function toggle(Employee $employee, EmployeeAllowance $allowance): RedirectResponse
    {
        $allowance->update(['active' => !$allowance->active]);

        $status = $allowance->active ? 'activated' : 'deactivated';
        return redirect()->route('employees.allowances.index', $employee)
            ->with('success', "Allowance {$status}.");
    }

    public function destroy(Employee $employee, EmployeeAllowance $allowance): RedirectResponse
    {
        $allowance->delete();
        return redirect()->route('employees.allowances.index', $employee)
            ->with('success', 'Allowance deleted.');
    }
}
