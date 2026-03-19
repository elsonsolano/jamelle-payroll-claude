<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeStandingDeduction;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeStandingDeductionController extends Controller
{
    public function index(Employee $employee): View
    {
        $employee->load('branch');
        $deductions = $employee->employeeStandingDeductions()->orderBy('type')->get();
        return view('deductions.index', compact('employee', 'deductions'));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'type'          => 'required|in:SSS,PhilHealth,PagIBIG,loan,cash_advance,uniform,other',
            'amount'        => 'required|numeric|min:0',
            'description'   => 'nullable|string|max:255',
            'cutoff_period' => 'required|in:both,first,second',
        ]);

        $validated['employee_id'] = $employee->id;
        $validated['active']      = true;

        EmployeeStandingDeduction::create($validated);

        return redirect()->route('employees.deductions.index', $employee)
            ->with('success', 'Deduction added successfully.');
    }

    public function update(Request $request, Employee $employee, EmployeeStandingDeduction $deduction): RedirectResponse
    {
        $validated = $request->validate([
            'type'          => 'required|in:SSS,PhilHealth,PagIBIG,loan,cash_advance,uniform,other',
            'amount'        => 'required|numeric|min:0',
            'description'   => 'nullable|string|max:255',
            'cutoff_period' => 'required|in:both,first,second',
        ]);

        $deduction->update($validated);

        return redirect()->route('employees.deductions.index', $employee)
            ->with('success', 'Deduction updated successfully.');
    }

    public function toggle(Employee $employee, EmployeeStandingDeduction $deduction): RedirectResponse
    {
        $deduction->update(['active' => !$deduction->active]);

        $status = $deduction->active ? 'activated' : 'deactivated';
        return redirect()->route('employees.deductions.index', $employee)
            ->with('success', "Deduction {$status}.");
    }

    public function destroy(Employee $employee, EmployeeStandingDeduction $deduction): RedirectResponse
    {
        $deduction->delete();
        return redirect()->route('employees.deductions.index', $employee)
            ->with('success', 'Deduction deleted.');
    }
}
